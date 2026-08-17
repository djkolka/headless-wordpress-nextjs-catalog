import Image from "next/image";
import Link from "next/link";
import type { Product } from "@/lib/schemas";

type CardProduct = Pick<
  Product,
  "slug" | "title" | "excerpt" | "sku" | "price" | "featuredImage"
>;

export function ProductCard({ product }: { product: CardProduct }) {
  return (
    <article className="product-card">
      <Link
        className="product-card__media"
        href={`/products/${product.slug}`}
        aria-label={`View ${product.title}`}
      >
        {product.featuredImage ? (
          <Image
            src={product.featuredImage.url}
            alt={product.featuredImage.alt}
            width={product.featuredImage.width}
            height={product.featuredImage.height}
            sizes="(max-width: 700px) 100vw, 33vw"
          />
        ) : (
          <span aria-hidden="true" className="product-placeholder">
            {product.sku}
          </span>
        )}
      </Link>
      <div className="product-card__body">
        <p className="eyebrow">{product.sku}</p>
        <h2>
          <Link href={`/products/${product.slug}`}>{product.title}</Link>
        </h2>
        <p>{product.excerpt}</p>
        <p className="price">{product.price.formatted}</p>
      </div>
    </article>
  );
}
