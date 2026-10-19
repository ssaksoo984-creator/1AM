<?php
/**
 * 1AM 기본 페이지 만들기 (관리자 Pages 화면의 "Set up 1AM pages" 버튼)
 *
 * About · FAQ · Return Policy · Terms & Conditions(+ Vaping Product Compliance)
 * - 없는 페이지는 새로 만들고, 내용이 비어 있는 페이지는 채웁니다.
 * - 이미 내용이 있는 페이지는 건드리지 않습니다.
 *   (Terms 만 예외: "Vaping Product Compliance" 섹션이 없으면 맨 아래에 덧붙임)
 * - 만든 뒤에는 블록 편집기에서 자유롭게 고치면 됩니다.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------------------
 * 블록 마크업 도우미
 * ------------------------------------------------------------------------- */
function oneam_b_p( $text ) {
	return "<!-- wp:paragraph -->\n<p>" . $text . "</p>\n<!-- /wp:paragraph -->\n\n";
}
function oneam_b_h( $text, $level = 2, $anchor = '' ) {
	$attr = 2 === $level ? '' : ' {"level":' . (int) $level . '}';
	if ( $anchor ) {
		$attr = ' {' . ( 2 === $level ? '' : '"level":' . (int) $level . ',' ) . '"anchor":"' . $anchor . '"}';
	}
	$id = $anchor ? ' id="' . $anchor . '"' : '';
	return "<!-- wp:heading{$attr} -->\n<h{$level} class=\"wp-block-heading\"{$id}>" . $text . "</h{$level}>\n<!-- /wp:heading -->\n\n";
}
function oneam_b_details( $q, $a ) {
	return "<!-- wp:details -->\n<details class=\"wp-block-details\"><summary>" . $q . "</summary>" . oneam_b_p( $a ) . "</details>\n<!-- /wp:details -->\n\n";
}
function oneam_b_list( $items ) {
	$out = "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
	foreach ( $items as $it ) {
		$out .= "<!-- wp:list-item -->\n<li>" . $it . "</li>\n<!-- /wp:list-item -->";
	}
	return $out . "</ul>\n<!-- /wp:list -->\n\n";
}

/* ---------------------------------------------------------------------------
 * 내용
 * ------------------------------------------------------------------------- */
function oneam_compliance_blocks() {
	$co = esc_html( oneam_opt( 'oneam_company' ) );
	return oneam_b_h( 'Vaping Product Compliance – Wholesale Sales Only', 2, 'vape-compliance' )
		. oneam_b_h( '1. Authorized Retailers Only', 3 )
		. oneam_b_p( "All vaping products offered through this website are sold by {$co} exclusively to verified and approved adult-only vape retailers. Sales to individual consumers are strictly prohibited." )
		. oneam_b_p( "By placing an order, the purchaser confirms that their business is legally authorized to purchase and resell vaping products and that all business information provided is accurate and current. Any changes affecting the business’s eligibility must be promptly reported to {$co}" )
		. oneam_b_h( '2. Compliance with Federal and Provincial Regulations', 3 )
		. oneam_b_p( 'Purchasers are responsible for ensuring that their retail activities comply with all applicable federal, provincial, territorial, and local laws and regulations governing the sale of vaping products.' )
		. oneam_b_p( 'This includes, but is not limited to, age restrictions, product restrictions, sales and advertising requirements, and any applicable regulatory reporting obligations.' )
		. oneam_b_h( '3. Excise Stamp and Provincial Sales Compliance', 3 )
		. oneam_b_p( 'All vaping products must be sold in accordance with applicable Canadian excise stamp requirements.' )
		. oneam_b_p( 'Purchasers must ensure that products are offered for retail sale only in jurisdictions where their respective excise stamps are valid.' )
		. oneam_b_p( 'Products must not be transferred, redistributed, or resold in a manner that violates applicable excise stamp or provincial regulations.' )
		. oneam_b_p( "We ship only products stamped for your store's province, and only to the licensed store locations registered on your account. Purchasers must check each delivery and promptly notify {$co} of any stamp discrepancy." )
		. oneam_b_h( '4. Product Integrity and Unauthorized Modifications', 3 )
		. oneam_b_p( 'Purchasers must not alter, tamper with, or remove any original product packaging, nicotine contents, warning labels, regulatory markings, or excise stamps, except as expressly permitted or required by applicable law.' )
		. oneam_b_p( 'Unauthorized modifications or repackaging of products are strictly prohibited.' )
		. oneam_b_h( '5. Product Safety and Recalls', 3 )
		. oneam_b_p( "Purchasers must promptly notify {$co} of any known product defects, safety concerns, or suspected regulatory non-compliance." )
		. oneam_b_p( 'In the event of a product recall or safety-related corrective action, purchasers must immediately cease the sale of affected products upon notification and cooperate with all applicable recall and corrective procedures.' )
		. oneam_b_h( '6. Right to Refuse Orders or Suspend Accounts', 3 )
		. oneam_b_p( "{$co} reserves the right to request additional business verification, refuse or cancel orders, or suspend or terminate accounts if a purchaser fails to meet eligibility requirements, provides inaccurate information, or is reasonably suspected of violating applicable vaping regulations." )
		. oneam_b_p( "Nothing in these terms limits any legal obligations imposed on {$co} by applicable law." );
}

function oneam_faq_items() {
	$email = oneam_opt( 'oneam_email' );
	$co    = oneam_opt( 'oneam_company' );
	return array(
		array( 'Who can purchase products from 1AM?', '1AM products are available exclusively to verified and approved adult-only vape retailers in ' . oneam_regions_phrase() . '. We do not sell directly to individual consumers.' ),
		array( 'How do I open a retail account?', 'Click “Apply for wholesale” and complete the account application with your business information and required documentation. Once your business has been verified and approved by our team, you will receive access to our online ordering system.' ),
		array( 'What documents are required for account approval?', 'We require valid business information and supporting documentation confirming that your business operates as an authorized adult-only vape retail store. Additional documentation may be requested during the verification process.' ),
		array( 'Can I view wholesale pricing without an approved account?', 'No. Wholesale pricing and online ordering are available only to verified and approved retail account holders.' ),
		array( 'Which provinces do you supply?', 'We currently supply licensed adult-only vape stores in ' . oneam_regions_phrase() . '. Stores in other provinces and territories are not supported yet.' ),
		array( 'Which excise stamp will my products have?', 'We ship only products stamped for your store\'s province: ' . implode( ', ', array_map( function ( $c ) { return ( oneam_provinces()[ $c ] ?? $c ) . ' — ' . oneam_stamp_label( $c ); }, oneam_sales_regions() ) ) . '. Orders ship only to the licensed store locations registered on your account. If you operate stores in more than one province, contact us to add them to your account.' ),
		array( 'Is there a minimum order quantity (MOQ)?', 'Minimum order quantities may vary by product. Please refer to the product details or contact our sales team for more information.' ),
		array( 'What payment methods are accepted?', 'We accept ' . oneam_opt( 'oneam_payments' ) . '.' . ( oneam_opt( 'oneam_processor' ) ? ' Card payments are processed securely by ' . oneam_opt( 'oneam_processor' ) . '.' : ' After placing your order, send an Interac e-Transfer for the order total to ' . ( function_exists( 'oneam_etransfer_email' ) ? oneam_etransfer_email() : $email ) . ' with your order number in the message.' ) . ' All prices are in Canadian dollars (CAD), before GST.' ),
		array( 'How long does shipping take?', 'Orders ship within ' . oneam_opt( 'oneam_dispatch_time' ) . ' after payment is received, by Purolator Ground, UPS Ground or Canpar Ground. Delivery typically takes ' . oneam_opt( 'oneam_transit_time' ) . ' after shipping, depending on your location. ' . oneam_shipping_rates_sentence() ),
		array( 'What should I do if I receive damaged, defective, or incorrect products?', 'Please contact our customer service team with your order number, product details, and supporting photos. Our team will review the issue and provide further assistance.' ),
		array( 'Can I cancel or modify an order?', 'Please contact us as soon as possible if you need to cancel or modify your order. Changes may not be possible once the order has been processed or shipped.' ),
		array( 'How can I contact your team?', "For account registration, product inquiries, orders, or customer support, please contact us at {$email}. Distributed by {$co}" ),
	);
}

function oneam_setup_pages() {
	$email = esc_html( oneam_opt( 'oneam_email' ) );
	$co    = esc_html( oneam_opt( 'oneam_company' ) );
	$mail  = '<a href="mailto:' . $email . '">' . $email . '</a>';

	$faq = '';
	foreach ( oneam_faq_items() as $q ) {
		$faq .= oneam_b_details( esc_html( $q[0] ), str_replace( $email, $mail, esc_html( $q[1] ) ) );
	}

	return array(
		'about-us'      => array(
			'title'    => 'About',
			'template' => 'template-about.php',
			'content'  => oneam_b_h( 'Made for Canadian retail.' )
				. oneam_b_p( "1AM is a vape brand built for adult-only vape retailers in " . oneam_regions_phrase() . ". Our products are distributed by {$co} and sold exclusively to verified, approved retail partners — never directly to consumers." )
				. oneam_b_h( 'What we make', 3 )
				. oneam_b_p( 'Our first line, <strong>Slim HYBRID</strong>, is a slim disposable with a crystal-clear body, 2ml of e-liquid and 15 flavours across three families: <em>Fruit Ice</em>, <em>Mint</em> and <em>Classic Ice</em>. HYBRID Max and HYBRID Refill are in development.' )
				. oneam_b_h( 'How we work', 3 )
				. oneam_b_list(
					array(
						'<strong>Wholesale only.</strong> Every account is reviewed and verified before it can see pricing or place orders.',
						'<strong>Compliance first.</strong> Products are supplied for sale in line with Canadian excise stamp and provincial requirements.',
						'<strong>Product integrity.</strong> Original packaging, warning labels and regulatory markings stay intact from our warehouse to your shelf.',
						'<strong>Fast, tracked shipping.</strong> Orders ship by Purolator, UPS or Canpar Ground within ' . oneam_opt( 'oneam_dispatch_time' ) . ' of payment and typically arrive ' . oneam_opt( 'oneam_transit_time' ) . ' after shipping.',
					)
				)
				. oneam_b_h( 'Work with us', 3 )
				. oneam_b_p( "Run an adult-only vape store in " . oneam_regions_phrase() . "? Apply for a wholesale account, or reach our sales team at {$mail}." ),
		),
		'faq'           => array(
			'title'    => 'FAQ',
			'template' => '',
			'content'  => oneam_b_p( 'Answers for retailers. Can’t find what you need? Email us at ' . $mail . '.' ) . $faq,
		),
		'how-to-order'  => array(
			'title'    => 'How to Order',
			'template' => 'template-how-to-order.php',
			'content'  => oneam_b_p( 'New to 1AM? Here is how to open a retail account and place your first wholesale order.' ),
		),
		'return-policy' => array(
			'title'    => 'Return Policy',
			'template' => '',
			'content'  => oneam_policy_returns(),
			'policy'   => true,
		),
		'shipping-policy' => array(
			'title'    => 'Shipping Policy',
			'template' => '',
			'content'  => oneam_policy_shipping(),
			'policy'   => true,
		),
		'terms'         => array(
			'title'    => 'Terms & Conditions',
			'template' => '',
			'content'  => oneam_policy_terms(),
			'append'   => 'Vaping Product Compliance',
			'policy'   => true,
		),
		'privacy-policy' => array(
			'title'    => 'Privacy Policy',
			'template' => '',
			'content'  => oneam_policy_privacy(),
			'policy'   => true,
		),
	);
}

/** 실행: 결과 목록 반환 */
function oneam_run_setup_pages( $overwrite = false, $policies = false ) {
	$report = array();
	foreach ( oneam_setup_pages() as $slug => $pg ) {
		$page = get_page_by_path( $slug );
		// "정책 전체 글로 교체": 약관 · 반품 · 배송 · 개인정보 페이지 내용을 1AM 전체 글로
		if ( $policies ) {
			if ( empty( $pg['policy'] ) ) {
				continue;
			}
			$id = $page ? $page->ID : wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $pg['title'], 'post_name' => $slug ) );
			wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $pg['content'], 'post_status' => 'publish' ) ) );
			if ( 'builder' === get_post_meta( $id, '_elementor_edit_mode', true ) && function_exists( 'oneam_fill_page' ) ) {
				oneam_fill_page( $id, $slug ); // Elementor 글 위젯도 새 글로
			}
			if ( 'privacy-policy' === $slug ) {
				update_option( 'wp_page_for_privacy_policy', $id );
			}
			$report[] = $pg['title'] . ': full 1AM text';
			continue;
		}
		if ( ! $page ) {
			$id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $pg['title'],
					'post_name'    => $slug,
					'post_content' => $pg['content'],
				)
			);
			if ( $pg['template'] ) {
				update_post_meta( $id, '_wp_page_template', $pg['template'] );
			}
			$report[] = $pg['title'] . ': created';
		} elseif ( ( $overwrite && empty( $pg['append'] ) ) || ( '' === trim( wp_strip_all_tags( $page->post_content ) ) && 'builder' !== get_post_meta( $page->ID, '_elementor_edit_mode', true ) ) ) {
			if ( $overwrite ) {
				delete_post_meta( $page->ID, '_elementor_edit_mode' ); // Elementor 로 만든 페이지면 블록 내용이 보이도록
			}
			wp_update_post( array( 'ID' => $page->ID, 'post_content' => $pg['content'] ) );
			if ( $pg['template'] && ! get_post_meta( $page->ID, '_wp_page_template', true ) ) {
				update_post_meta( $page->ID, '_wp_page_template', $pg['template'] );
			}
			$report[] = $pg['title'] . ( $overwrite ? ': replaced with 1AM text' : ': filled' );
		} elseif ( ! empty( $pg['append'] ) && false === strpos( $page->post_content, $pg['append'] ) ) {
			wp_update_post( array( 'ID' => $page->ID, 'post_content' => $page->post_content . "\n\n" . $pg['content'] ) );
			$report[] = $pg['title'] . ': compliance section added';
		} else {
			$report[] = $pg['title'] . ': already has content (left as is)';
		}
	}
	if ( function_exists( 'oneam_setup_checkout_terms' ) && oneam_setup_checkout_terms() ) {
		$report[] = 'Checkout: terms checkbox required, with links to Terms, Shipping and Return policies';
	}
	// WooCommerce 결제 화면의 "약관 동의" 에 Terms 페이지 연결 (비어 있을 때만)
	$terms = get_page_by_path( 'terms' );
	if ( $terms && class_exists( 'WooCommerce' ) && ! (int) get_option( 'woocommerce_terms_page_id' ) ) {
		update_option( 'woocommerce_terms_page_id', $terms->ID );
		$report[] = 'WooCommerce checkout: linked to Terms & Conditions';
	}
	return $report;
}

add_action(
	'admin_post_oneam_setup_pages',
	function () {
		check_admin_referer( 'oneam_setup_pages' );
		if ( ! current_user_can( 'publish_pages' ) ) {
			wp_die( 'Not allowed.' );
		}
		set_transient( 'oneam_setup_report', oneam_run_setup_pages( ! empty( $_GET['overwrite'] ), ! empty( $_GET['policies'] ) ), 120 );
		wp_safe_redirect( admin_url( 'edit.php?post_type=page' ) );
		exit;
	}
);

add_action(
	'admin_notices',
	function () {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-page' !== $screen->id || ! current_user_can( 'publish_pages' ) ) {
			return;
		}
		$report = get_transient( 'oneam_setup_report' );
		if ( $report ) {
			delete_transient( 'oneam_setup_report' );
			echo '<div class="notice notice-success is-dismissible"><p><strong>1AM pages:</strong></p><ul style="list-style:disc;margin-left:20px">';
			foreach ( $report as $line ) {
				echo '<li>' . esc_html( $line ) . '</li>';
			}
			echo '</ul></div>';
		}
		$missing = array();
		foreach ( oneam_setup_pages() as $slug => $pg ) {
			if ( ! get_page_by_path( $slug ) ) {
				$missing[] = $pg['title'];
			}
		}
		$url  = wp_nonce_url( admin_url( 'admin-post.php?action=oneam_setup_pages' ), 'oneam_setup_pages' );
		$over = wp_nonce_url( admin_url( 'admin-post.php?action=oneam_setup_pages&overwrite=1' ), 'oneam_setup_pages' );
		$pol  = wp_nonce_url( admin_url( 'admin-post.php?action=oneam_setup_pages&policies=1' ), 'oneam_setup_pages' );
		printf(
			'<div class="notice notice-info"><p><strong>1AM pages:</strong> %s <a class="button" href="%s">Set up 1AM pages</a> <a class="button-link" href="%s" onclick="return confirm(\'Replace the text of About, FAQ and Return Policy with the 1AM text? Their current content will be replaced (you can restore it from Revisions).\');">Replace About, FAQ &amp; Return Policy text</a> · <a class="button-link" href="%s" onclick="return confirm(\'Replace Terms, Return Policy, Shipping Policy and Privacy Policy with the full 1AM text? Their current text will be replaced (you can restore it from Revisions).\');">Replace policy pages with the full 1AM text</a></p></div>',
			$missing ? esc_html( 'Missing: ' . implode( ', ', $missing ) . '. Create them with the 1AM text (About, FAQ, Return Policy, Terms with vape compliance).' ) : 'About, FAQ, Return Policy and Terms are set up. Pages that already have content are never overwritten.',
			esc_url( $url ),
			esc_url( $over ),
			esc_url( $pol )
		);
	}
);

/** About 페이지는 템플릿을 따로 고르지 않아도 1AM About 레이아웃으로 */
add_filter(
	'template_include',
	function ( $template ) {
		if ( ! is_page() || oneam_is_builder_page() || oneam_is_builder_preview() ) {
			return $template;
		}
		$chosen = get_page_template_slug();
		$about  = trim( (string) wp_parse_url( oneam_opt( 'oneam_about_url' ), PHP_URL_PATH ), '/' );
		if ( ! $chosen && $about && get_post_field( 'post_name', get_queried_object_id() ) === basename( $about ) ) {
			$t = locate_template( 'template-about.php' );
			return $t ? $t : $template;
		}
		return $template;
	}
);

/** How to Order 정보 카드 기본 내용 */
function oneam_orderinfo_defaults() {
	$email = oneam_opt( 'oneam_email' );
	return array(
		array(
			'icon'  => 'check',
			'title' => 'Before you order',
			'lines' => "We ship only products stamped for your store's province.\nWe supply stores in " . oneam_regions_phrase() . ".\nMinimum order quantities may vary by product.\nConfirm Vaping Product Compliance at checkout.",
		),
		array(
			'icon'  => 'card',
			'title' => 'Payment',
			'lines' => oneam_opt( 'oneam_payments' ) . "\n" . ( oneam_opt( 'oneam_processor' ) ? 'Cards processed securely by ' . oneam_opt( 'oneam_processor' ) : 'Send the order total to ' . ( function_exists( 'oneam_etransfer_email' ) ? oneam_etransfer_email() : $email ) . ' with your order number' ) . "\nPrices in CAD, before GST · shown after you log in.",
		),
		array(
			'icon'  => 'truck',
			'title' => 'Shipping',
			'lines' => "Purolator Ground, UPS Ground or Canpar Ground\nShips within " . oneam_opt( 'oneam_dispatch_time' ) . " of payment · delivery " . oneam_opt( 'oneam_transit_time' ) . " after shipping\nAll orders are final sale — see our Return Policy.",
		),
		array(
			'icon'  => 'mail',
			'title' => 'Need help?',
			'lines' => "Stores in more than one province? Ask us to add them to your account.\nDamaged or incorrect items: send your order number and photos.\n{$email}",
		),
	);
}

/** How to Order 페이지도 템플릿을 고르지 않아도 1AM 레이아웃으로 */
add_filter(
	'template_include',
	function ( $template ) {
		if ( ! is_page() || oneam_is_builder_page() || oneam_is_builder_preview() || get_page_template_slug() ) {
			return $template;
		}
		$order = trim( (string) wp_parse_url( oneam_opt( 'oneam_order_url' ), PHP_URL_PATH ), '/' );
		if ( $order && get_post_field( 'post_name', get_queried_object_id() ) === basename( $order ) ) {
			$t = locate_template( 'template-how-to-order.php' );
			return $t ? $t : $template;
		}
		return $template;
	},
	11
);
