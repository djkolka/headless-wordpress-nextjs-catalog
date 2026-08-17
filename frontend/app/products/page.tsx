import type { Metadata } from "next";
import { connection } from "next/server";
import { CatalogFilters } from "@/components/catalog-filters";
import { EmptyState } from "@/components/states";
import { Pagination } from "@/components/pagination";
import { ProductCard } from "@/components/product-card";
import { fetchProducts, type ProductQuery } from "@/lib/wordpress";

export const metadata: Metadata = {
  title: "Products",
  description:
    "Browse commercial automation sensors, controllers, connectivity, and monitoring products.",
  alternates: { canonical: "/products" },
  openGraph: {
    title: "Automation Products",
    description: "Browse the Fieldline commercial automation catalog.",
    url: "/products",
    type: "website",
  },
};

type SearchParams = Record<string, string | string[] | undefined>;
function one(value: string | string[] | undefined): string | undefined {
  return typeof value === "string" ? value : undefined;
}
function toQuery(params: SearchParams): ProductQuery {
  const page = Number(one(params.page));
  const sort = one(params.sort);
  const order = one(params.order);
  return {
    page: Number.isInteger(page) && page > 0 ? page : 1,
    perPage: 12,
    search: one(params.search),
    category: one(params.category),
    manufacturer: one(params.manufacturer),
    application: one(params.application),
    sort: ["date", "modified", "title", "price"].includes(sort ?? "")
      ? (sort as ProductQuery["sort"])
      : "date",
    order: ["asc", "desc"].includes(order ?? "")
      ? (order as ProductQuery["order"])
      : "desc",
  };
}

export default async function ProductsPage({
  searchParams,
}: {
  searchParams: Promise<SearchParams>;
}) {
  await connection();
  const query = toQuery(await searchParams);
  const result = await fetchProducts(query);
  return (
    <div className="page-shell">
      <header className="hero">
        <p className="eyebrow">Commercial automation</p>
        <h1>Products built for the field</h1>
        <p>
          Explore a focused selection of sensors, control, and monitoring
          hardware.
        </p>
      </header>
      <CatalogFilters query={query} />
      <div className="results-header">
        <h2>
          {result.pagination.total}{" "}
          {result.pagination.total === 1 ? "product" : "products"}
        </h2>
      </div>
      {result.data.length ? (
        <div className="product-grid">
          {result.data.map((product) => (
            <ProductCard product={product} key={product.id} />
          ))}
        </div>
      ) : (
        <EmptyState />
      )}
      <Pagination
        query={query}
        page={result.pagination.page}
        totalPages={result.pagination.totalPages}
      />
    </div>
  );
}
