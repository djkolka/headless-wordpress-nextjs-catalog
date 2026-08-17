import { z } from "zod";

export const imageSchema = z.object({
  url: z.string().url(),
  alt: z.string(),
  width: z.number().int().positive(),
  height: z.number().int().positive(),
});

export const taxonomyTermSchema = z.object({
  id: z.number().int().positive(),
  slug: z.string(),
  name: z.string(),
});

const priceSchema = z.object({
  amount: z.number().nonnegative(),
  currency: z.string().regex(/^[A-Z]{3}$/),
  formatted: z.string(),
});
const relatedProductSchema = z.object({
  id: z.number().int().positive(),
  slug: z.string(),
  title: z.string(),
  excerpt: z.string(),
  sku: z.string(),
  price: priceSchema,
  featuredImage: imageSchema.nullable(),
});

export const productSchema = z.object({
  id: z.number().int().positive(),
  slug: z.string(),
  title: z.string(),
  status: z.string(),
  excerpt: z.string(),
  content: z.string(),
  sku: z.string(),
  price: priceSchema,
  featuredImage: imageSchema.nullable(),
  gallery: z.array(imageSchema),
  specifications: z.array(z.object({ label: z.string(), value: z.string() })),
  datasheetUrl: z.string().url().nullable(),
  taxonomies: z.object({
    categories: z.array(taxonomyTermSchema),
    manufacturers: z.array(taxonomyTermSchema),
    applications: z.array(taxonomyTermSchema),
  }),
  relatedProducts: z.array(relatedProductSchema),
  publishedAt: z.iso.datetime({ offset: true }),
  modifiedAt: z.iso.datetime({ offset: true }),
});

export const productCollectionSchema = z.object({
  data: z.array(productSchema),
  pagination: z.object({
    page: z.number().int().positive(),
    perPage: z.number().int().positive(),
    total: z.number().int().nonnegative(),
    totalPages: z.number().int().nonnegative(),
  }),
});

export type Product = z.infer<typeof productSchema>;
export type ProductCollection = z.infer<typeof productCollectionSchema>;
export type TaxonomyTerm = z.infer<typeof taxonomyTermSchema>;
