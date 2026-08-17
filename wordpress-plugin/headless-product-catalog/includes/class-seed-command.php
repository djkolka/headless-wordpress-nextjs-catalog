<?php

declare(strict_types=1);

namespace Headless_Catalog;

final class Seed_Command {
	private const SOURCE = 'headless-catalog-demo-v1';

	/**
	 * Create or update the fictional demo catalog without deleting other content.
	 *
	 * ## EXAMPLES
	 *
	 *     wp catalog seed
	 */
	public function __invoke(): void {
		Product_Post_Type::register();
		$counts = array(
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
		);
		$ids    = array();

		foreach ( $this->products() as $product ) {
			$existing = $this->find_generated( $product['slug'] );
			$postarr  = array(
				'ID'           => $existing,
				'post_type'    => Product_Post_Type::POST_TYPE,
				'post_status'  => 'publish',
				'post_name'    => $product['slug'],
				'post_title'   => $product['title'],
				'post_excerpt' => $product['short_description'],
				'post_content' => $product['content'],
			);

			$id = $existing ? wp_update_post( wp_slash( $postarr ), true ) : wp_insert_post( wp_slash( $postarr ), true );
			if ( is_wp_error( $id ) ) {
				\WP_CLI::warning( sprintf( 'Skipped %s: %s', $product['title'], $id->get_error_message() ) );
				++$counts['skipped'];
				continue;
			}

			$id                      = (int) $id;
			$ids[ $product['slug'] ] = $id;
			update_post_meta( $id, '_hpcatalog_seed_source', self::SOURCE );
			foreach ( array( 'sku', 'short_description', 'price', 'currency', 'specifications', 'datasheet_url' ) as $field ) {
				Field_Repository::set( $id, $field, $product[ $field ] );
			}
			$this->set_terms( $id, Product_Post_Type::TAXONOMIES['categories'], $product['category'] );
			$this->set_terms( $id, Product_Post_Type::TAXONOMIES['manufacturers'], $product['manufacturer'] );
			$this->set_terms( $id, Product_Post_Type::TAXONOMIES['applications'], $product['applications'] );
			++$counts[ $existing ? 'updated' : 'created' ];
		}

		foreach ( $this->products() as $product ) {
			if ( isset( $ids[ $product['slug'] ] ) ) {
				$related = array_values( array_filter( array_map( static fn( string $slug ): ?int => $ids[ $slug ] ?? null, $product['related'] ) ) );
				Field_Repository::set( $ids[ $product['slug'] ], 'related_products', $related );
			}
		}

		\WP_CLI::success( sprintf( 'Catalog seed complete: %d created, %d updated, %d skipped. No existing content was deleted.', $counts['created'], $counts['updated'], $counts['skipped'] ) );
	}

	private function find_generated( string $slug ): int {
		$posts = get_posts(
			array(
				'post_type'      => Product_Post_Type::POST_TYPE,
				'post_status'    => 'any',
				'name'           => $slug,
				'meta_key'       => '_hpcatalog_seed_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => self::SOURCE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		return $posts ? (int) $posts[0] : 0;
	}

	/** @param string|array<int,string> $names */
	private function set_terms( int $post_id, string $taxonomy, $names ): void {
		$term_ids = array();
		foreach ( (array) $names as $name ) {
			$term = term_exists( $name, $taxonomy );
			if ( ! $term ) {
				$term = wp_insert_term( $name, $taxonomy );
			}
			if ( ! is_wp_error( $term ) ) {
				$term_ids[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
			}
		}
		wp_set_object_terms( $post_id, $term_ids, $taxonomy, false );
	}

	/** @return array<int,array<string,mixed>> */
	private function products(): array {
		$base = 'https://example.com/datasheets/';
		return array(
			$this->product( 'temperature-sensor-t100', 'Temperature Sensor T100', 'T100', 129.99, 'Environmental Sensors', 'Northstar Controls', array( 'Cold Storage', 'Building Automation' ), array( array( 'Operating range', '-20°F to 180°F' ), array( 'Output', '4–20 mA' ) ), array( 'pressure-transmitter-p220', 'edge-controller-ec4' ), $base ),
			$this->product( 'pressure-transmitter-p220', 'Pressure Transmitter P220', 'P220', 184.50, 'Process Sensors', 'Northstar Controls', array( 'Water Treatment', 'Process Monitoring' ), array( array( 'Range', '0–300 PSI' ), array( 'Accuracy', '±0.25% FS' ) ), array( 'temperature-sensor-t100', 'signal-isolator-si2' ), $base ),
			$this->product( 'edge-controller-ec4', 'Edge Controller EC4', 'EC4', 749.00, 'Controllers', 'Asterion Automation', array( 'Building Automation', 'Remote Monitoring' ), array( array( 'Digital inputs', '16' ), array( 'Protocol', 'BACnet/IP, Modbus TCP' ) ), array( 'io-expansion-x16', 'industrial-gateway-ig5' ), $base ),
			$this->product( 'io-expansion-x16', 'I/O Expansion X16', 'X16', 269.00, 'Controllers', 'Asterion Automation', array( 'Machine Control', 'Building Automation' ), array( array( 'Channels', '16 universal' ), array( 'Isolation', '1.5 kV' ) ), array( 'edge-controller-ec4' ), $base ),
			$this->product( 'variable-speed-drive-vsd7', 'Variable Speed Drive VSD7', 'VSD7', 1125.00, 'Motor Control', 'Blueforge Motion', array( 'Material Handling', 'HVAC' ), array( array( 'Power', '7.5 HP' ), array( 'Input', '480 VAC, 3-phase' ) ), array( 'motor-protection-relay-mpr8', 'vibration-monitor-vm3' ), $base ),
			$this->product( 'motor-protection-relay-mpr8', 'Motor Protection Relay MPR8', 'MPR8', 489.00, 'Motor Control', 'Blueforge Motion', array( 'Material Handling', 'Pump Systems' ), array( array( 'Current range', '1–80 A' ), array( 'Trip classes', '5–30' ) ), array( 'variable-speed-drive-vsd7' ), $base ),
			$this->product( 'industrial-gateway-ig5', 'Industrial Gateway IG5', 'IG5', 595.00, 'Connectivity', 'Juniper Peak Systems', array( 'Remote Monitoring', 'Process Monitoring' ), array( array( 'Ethernet ports', '2' ), array( 'Protocols', 'OPC UA, MQTT, Modbus' ) ), array( 'edge-controller-ec4', 'wireless-node-wn2' ), $base ),
			$this->product( 'wireless-node-wn2', 'Wireless Sensor Node WN2', 'WN2', 215.75, 'Connectivity', 'Juniper Peak Systems', array( 'Remote Monitoring', 'Cold Storage' ), array( array( 'Radio', 'LoRaWAN' ), array( 'Battery life', 'Up to 5 years' ) ), array( 'industrial-gateway-ig5', 'temperature-sensor-t100' ), $base ),
			$this->product( 'signal-isolator-si2', 'Signal Isolator SI2', 'SI2', 98.00, 'Signal Conditioning', 'Cindercone Instruments', array( 'Process Monitoring', 'Water Treatment' ), array( array( 'Input', '0–10 V / 4–20 mA' ), array( 'Channels', '2' ) ), array( 'pressure-transmitter-p220' ), $base ),
			$this->product( 'vibration-monitor-vm3', 'Vibration Monitor VM3', 'VM3', 638.00, 'Condition Monitoring', 'Cindercone Instruments', array( 'Predictive Maintenance', 'Material Handling' ), array( array( 'Frequency range', '2 Hz–10 kHz' ), array( 'Outputs', 'Relay, 4–20 mA' ) ), array( 'variable-speed-drive-vsd7', 'bearing-sensor-bs1' ), $base ),
			$this->product( 'bearing-sensor-bs1', 'Bearing Health Sensor BS1', 'BS1', 342.00, 'Condition Monitoring', 'Lumen Fieldworks', array( 'Predictive Maintenance', 'Pump Systems' ), array( array( 'Measurements', 'Vibration and temperature' ), array( 'Ingress rating', 'IP67' ) ), array( 'vibration-monitor-vm3', 'wireless-node-wn2' ), $base ),
			$this->product( 'level-switch-ls9', 'Capacitive Level Switch LS9', 'LS9', 156.25, 'Process Sensors', 'Lumen Fieldworks', array( 'Water Treatment', 'Process Monitoring' ), array( array( 'Probe length', '200 mm' ), array( 'Ingress rating', 'IP68' ) ), array( 'pressure-transmitter-p220', 'signal-isolator-si2' ), $base ),
		);
	}

	/** @param array<int,string> $applications @param array<int,array{0:string,1:string}> $specs @param array<int,string> $related @return array<string,mixed> */
	private function product( string $slug, string $title, string $sku, float $price, string $category, string $manufacturer, array $applications, array $specs, array $related, string $base ): array {
		$short = sprintf( 'Commercial-grade %s for reliable automation systems.', strtolower( $title ) );
		return array(
			'slug'              => $slug,
			'title'             => $title,
			'sku'               => $sku,
			'price'             => $price,
			'currency'          => 'USD',
			'short_description' => $short,
			'content'           => '<p>' . esc_html( $short ) . '</p><p>Designed for straightforward commissioning, documented integration, and dependable field operation.</p>',
			'category'          => $category,
			'manufacturer'      => $manufacturer,
			'applications'      => $applications,
			'specifications'    => array_map(
				static fn( array $row ): array => array(
					'spec_label' => $row[0],
					'spec_value' => $row[1],
				),
				$specs
			),
			'datasheet_url'     => $base . $slug . '.pdf',
			'related'           => $related,
		);
	}
}
