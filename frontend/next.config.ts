import type { NextConfig } from "next";

function wordpressPattern(): NonNullable<
  NextConfig["images"]
>["remotePatterns"] {
  if (!process.env.WORDPRESS_API_URL) return [];

  try {
    const url = new URL(process.env.WORDPRESS_API_URL);
    return [
      {
        protocol: url.protocol.replace(":", "") as "http" | "https",
        hostname: url.hostname,
        port: url.port,
      },
    ];
  } catch {
    return [];
  }
}

const config: NextConfig = {
  agentRules: false,
  images: { remotePatterns: wordpressPattern() },
  poweredByHeader: false,
};

export default config;
