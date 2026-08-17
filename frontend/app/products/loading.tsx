import { CatalogSkeleton } from "@/components/states";
export default function Loading() {
  return (
    <div className="page-shell">
      <div className="loading-heading" />
      <CatalogSkeleton />
    </div>
  );
}
