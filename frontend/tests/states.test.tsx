import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { CatalogSkeleton, EmptyState } from "@/components/states";
import ProductsError from "@/app/products/error";
describe("catalog states", () => {
  it("provides a useful empty state", () => {
    render(<EmptyState />);
    expect(
      screen.getByRole("heading", { name: "No products found" }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("link", { name: "Clear all filters" }),
    ).toHaveAttribute("href", "/products");
  });
  it("announces loading status", () => {
    render(<CatalogSkeleton />);
    expect(screen.getByLabelText("Loading products")).toHaveAttribute(
      "aria-busy",
      "true",
    );
  });
  it("offers recovery from an error", () => {
    const reset = vi.fn();
    render(<ProductsError error={new Error("failed")} reset={reset} />);
    expect(screen.getByRole("alert")).toHaveTextContent("Catalog unavailable");
    fireEvent.click(screen.getByRole("button", { name: "Try again" }));
    expect(reset).toHaveBeenCalledOnce();
  });
});
