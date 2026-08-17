import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { draftMode } from "next/headers";
import { notFound } from "next/navigation";
import { ProductCard } from "@/components/product-card";
import { requiredPublicUrl } from "@/lib/config";
import { sanitizeWordPressHtml } from "@/lib/sanitize";
import { CatalogApiError, fetchProduct } from "@/lib/wordpress";

async function getProduct(slug: string) {
  const preview = (await draftMode()).isEnabled;
  try {
    return await fetchProduct(slug, preview);
  } catch (error) {
    if (error instanceof CatalogApiError && error.status === 404) notFound();
    throw error;
  }
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const product = await getProduct(slug);
  const description = product.excerpt.slice(0, 160);
  const url = `/products/${product.slug}`;
  return {
    title: product.title,
    description,
    alternates: { canonical: url },
    openGraph: {
      title: product.title,
      description,
      url,
      type: "website",
      images: product.featuredImage
        ? [
            {
              url: product.featuredImage.url,
              width: product.featuredImage.width,
              height: product.featuredImage.height,
              alt: product.featuredImage.alt,
            },
          ]
        : [],
    },
  };
}

export default async function ProductPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const product = await getProduct(slug);
  const preview = (await draftMode()).isEnabled;
  const productUrl = new URL(
    `/products/${product.slug}`,
    requiredPublicUrl("NEXT_PUBLIC_SITE_URL"),
  ).toString();
  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "Product",
    name: product.title,
    description: product.excerpt,
    sku: product.sku,
    image: product.featuredImage?.url,
    brand: product.taxonomies.manufacturers[0]
      ? { "@type": "Brand", name: product.taxonomies.manufacturers[0].name }
      : undefined,
    offers: {
      "@type": "Offer",
      price: product.price.amount,
      priceCurrency: product.price.currency,
      availability: "https://schema.org/InStock",
      url: productUrl,
    },
  };
  return (
    <article className="page-shell product-detail">
      {preview && (
        <p className="preview-banner" role="status">
          Draft preview — this page is not public.
        </p>
      )}
      <nav aria-label="Breadcrumb">
        <Link href="/products">Products</Link>
        <span aria-hidden="true"> / </span>
        <span>{product.title}</span>
      </nav>
      <div className="product-intro">
        <div className="detail-media">
          {product.featuredImage ? (
            <Image
              priority
              src={product.featuredImage.url}
              alt={product.featuredImage.alt}
              width={product.featuredImage.width}
              height={product.featuredImage.height}
              sizes="(max-width: 800px) 100vw, 50vw"
            />
          ) : (
            <span className="product-placeholder" aria-hidden="true">
              {product.sku}
            </span>
          )}
        </div>
        <header>
          <p className="eyebrow">
            {product.taxonomies.categories
              .map((term) => term.name)
              .join(" · ") || "Catalog product"}
          </p>
          <h1>{product.title}</h1>
          <p className="lead">{product.excerpt}</p>
          <p className="price price--large">{product.price.formatted}</p>
          <dl className="quick-facts">
            <div>
              <dt>SKU</dt>
              <dd>{product.sku}</dd>
            </div>
            {product.taxonomies.manufacturers[0] && (
              <div>
                <dt>Manufacturer</dt>
                <dd>{product.taxonomies.manufacturers[0].name}</dd>
              </div>
            )}
          </dl>
          {product.datasheetUrl && (
            <a
              className="button"
              href={product.datasheetUrl}
              rel="noopener noreferrer"
            >
              View datasheet{" "}
              <span className="sr-only">for {product.title}</span>
            </a>
          )}
        </header>
      </div>
      <div className="detail-columns">
        <section>
          <h2>Product overview</h2>
          {/* Content is sanitized on the server with an explicit tag and attribute allow-list. */}
          <div
            className="prose"
            dangerouslySetInnerHTML={{
              __html: sanitizeWordPressHtml(product.content),
            }}
          />
        </section>
        {product.specifications.length > 0 && (
          <section>
            <h2>Specifications</h2>
            <dl className="specs">
              {product.specifications.map((spec) => (
                <div key={`${spec.label}-${spec.value}`}>
                  <dt>{spec.label}</dt>
                  <dd>{spec.value}</dd>
                </div>
              ))}
            </dl>
          </section>
        )}
      </div>
      {product.relatedProducts.length > 0 && (
        <section className="related">
          <h2>Related products</h2>
          <div className="product-grid">
            {product.relatedProducts.map((item) => (
              <ProductCard product={item} key={item.id} />
            ))}
          </div>
        </section>
      )}
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{
          __html: JSON.stringify(jsonLd).replace(/</g, "\\u003c"),
        }}
      />
    </article>
  );
}
