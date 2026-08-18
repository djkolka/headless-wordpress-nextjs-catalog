import type { NextConfig } from "next";

function wordpressPattern(): NonNullable<
  NextConfig["images"]
>["remotePatterns"] {
  if (!process.env.WORDPRESS_API_URL) return [];

  try {
    const url = new URL(process.env.WORDPRESS_API_URL);
    const protocol = url.protocol.replace(":", "") as "http" | "https";
    const pattern = { protocol, hostname: url.hostname, port: url.port };

    if (url.hostname.endsWith(".local")) {
      return [
        pattern,
        {
          ...pattern,
          protocol: protocol === "http" ? "https" : "http",
          port: "",
        },
      ];
    }

    return [pattern];
  } catch {
    return [];
  }
}

function usesLocalWordPress(): boolean {
  try {
    return new URL(process.env.WORDPRESS_API_URL ?? "").hostname.endsWith(
      ".local",
    );
  } catch {
    return false;
  }
}

const config: NextConfig = {
  agentRules: false,
  images: {
    remotePatterns: wordpressPattern(),
    unoptimized: process.env.NODE_ENV === "development" && usesLocalWordPress(),
  },
  poweredByHeader: false,
};

export default config;
