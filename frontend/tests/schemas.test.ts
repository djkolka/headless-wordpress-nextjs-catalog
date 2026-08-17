import { describe, expect, it } from "vitest";
import { productSchema } from "@/lib/schemas";

const valid = {
  id: 1,
  slug: "sensor",
  title: "Sensor",
  status: "publish",
  excerpt: "A sensor",
  content: "<p>Body</p>",
  sku: "S1",
  price: { amount: 10, currency: "USD", formatted: "$10.00" },
  featuredImage: null,
  gallery: [],
  specifications: [],
  datasheetUrl: null,
  taxonomies: { categories: [], manufacturers: [], applications: [] },
  relatedProducts: [],
  publishedAt: "2026-08-17T12:00:00+00:00",
  modifiedAt: "2026-08-17T12:00:00+00:00",
};
describe("productSchema", () => {
  it("accepts the normalized contract", () =>
    expect(productSchema.parse(valid).sku).toBe("S1"));
  it("rejects malformed external data", () =>
    expect(() =>
      productSchema.parse({
        ...valid,
        price: { amount: -1, currency: "usd", formatted: "bad" },
      }),
    ).toThrow());
});
