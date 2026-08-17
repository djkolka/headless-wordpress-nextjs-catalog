import {
  productCollectionSchema,
  productSchema,
  type Product,
  type ProductCollection,
} from "./schemas";

export type ProductQuery = {
  page?: number;
  perPage?: number;
  search?: string;
  category?: string;
  manufacturer?: string;
  application?: string;
  sort?: "date" | "modified" | "title" | "price";
  order?: "asc" | "desc";
};

export class CatalogApiError extends Error {
  constructor(
    message: string,
    readonly status: number,
    readonly code = "catalog_request_failed",
  ) {
    super(message);
  }
}

function apiBase(override?: string): string {
  const value = override ?? process.env.WORDPRESS_API_URL;
  if (!value)
    throw new CatalogApiError(
      "WORDPRESS_API_URL is not configured.",
      500,
      "catalog_not_configured",
    );
  return value.replace(/\/$/, "");
}

export function buildProductsUrl(
  query: ProductQuery = {},
  override?: string,
): string {
  const url = new URL(`${apiBase(override)}/products`);
  const mapping: Record<keyof ProductQuery, string> = {
    page: "page",
    perPage: "per_page",
    search: "search",
    category: "category",
    manufacturer: "manufacturer",
    application: "application",
    sort: "sort",
    order: "order",
  };
  for (const [key, parameter] of Object.entries(mapping) as [
    keyof ProductQuery,
    string,
  ][]) {
    const value = query[key];
    if (value !== undefined && value !== "")
      url.searchParams.set(parameter, String(value));
  }
  return url.toString();
}

async function decode(response: Response): Promise<unknown> {
  const body: unknown = await response.json().catch(() => null);
  if (!response.ok) {
    const error =
      body && typeof body === "object" ? (body as Record<string, unknown>) : {};
    throw new CatalogApiError(
      typeof error.message === "string"
        ? error.message
        : "The catalog service returned an error.",
      response.status,
      typeof error.code === "string" ? error.code : undefined,
    );
  }
  return body;
}

export async function fetchProducts(
  query: ProductQuery = {},
): Promise<ProductCollection> {
  const response = await fetch(buildProductsUrl(query), {
    next: { revalidate: 300, tags: ["products"] },
  });
  return productCollectionSchema.parse(await decode(response));
}

export async function fetchProduct(
  slug: string,
  preview = false,
): Promise<Product> {
  const url = new URL(`${apiBase()}/products/${encodeURIComponent(slug)}`);
  const headers = new Headers();
  const options: RequestInit & {
    next?: { revalidate?: number; tags?: string[] };
  } = {
    headers,
    next: { revalidate: 300, tags: ["products", `product:${slug}`] },
  };
  if (preview) {
    const username = process.env.WORDPRESS_APPLICATION_USERNAME;
    const password = process.env.WORDPRESS_APPLICATION_PASSWORD;
    if (!username || !password)
      throw new CatalogApiError(
        "WordPress preview credentials are not configured.",
        500,
        "preview_not_configured",
      );
    headers.set(
      "Authorization",
      `Basic ${Buffer.from(`${username}:${password}`).toString("base64")}`,
    );
    url.searchParams.set("preview", "true");
    options.cache = "no-store";
    delete options.next;
  }
  const response = await fetch(url, options);
  return productSchema.parse(await decode(response));
}
