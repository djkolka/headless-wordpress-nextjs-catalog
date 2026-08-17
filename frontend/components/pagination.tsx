import Link from "next/link";
import type { ProductQuery } from "@/lib/wordpress";

function href(query: ProductQuery, page: number): string {
  const params = new URLSearchParams();
  Object.entries({ ...query, page }).forEach(([key, value]) => {
    if (value !== undefined && value !== "") params.set(key, String(value));
  });
  return `/products?${params.toString()}`;
}

export function Pagination({
  query,
  page,
  totalPages,
}: {
  query: ProductQuery;
  page: number;
  totalPages: number;
}) {
  if (totalPages <= 1) return null;
  return (
    <nav className="pagination" aria-label="Catalog pages">
      {page > 1 ? (
        <Link href={href(query, page - 1)} rel="prev">
          ← Previous
        </Link>
      ) : (
        <span />
      )}
      <span aria-live="polite">
        Page {page} of {totalPages}
      </span>
      {page < totalPages ? (
        <Link href={href(query, page + 1)} rel="next">
          Next →
        </Link>
      ) : (
        <span />
      )}
    </nav>
  );
}
