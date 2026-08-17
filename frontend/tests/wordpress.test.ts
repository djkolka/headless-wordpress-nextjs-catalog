import { describe, expect, it } from "vitest";
import { buildProductsUrl } from "@/lib/wordpress";
describe("buildProductsUrl", () => {
  it("serializes only supplied filters with WordPress parameter names", () => {
    const url = new URL(
      buildProductsUrl(
        {
          page: 2,
          perPage: 24,
          search: "pressure sensor",
          category: "process-sensors",
          sort: "price",
          order: "asc",
        },
        "https://wp.example/wp-json/catalog/v1",
      ),
    );
    expect(url.pathname).toBe("/wp-json/catalog/v1/products");
    expect(Object.fromEntries(url.searchParams)).toEqual({
      page: "2",
      per_page: "24",
      search: "pressure sensor",
      category: "process-sensors",
      sort: "price",
      order: "asc",
    });
  });
});
