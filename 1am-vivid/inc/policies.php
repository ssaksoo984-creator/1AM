<?php
/**
 * 1AM 정책 페이지 기본 글 (영문, 캐나다 도매 · 베이핑 기준)
 * Terms & Conditions(일반 약관 + Vaping Product Compliance) · Return Policy · Shipping Policy · Privacy Policy
 *
 * 페이지로 만들어지므로 블록 편집기 · Elementor 에서 자유롭게 수정할 수 있습니다.
 * 법률 검토 권장: 클레임 기간, 결제 기한, 준거법(현재 British Columbia), 개인정보 보관 기간.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 회사 연락처 (카드사 요구: 상호 · 주소 · 이메일 · 전화 · GST 번호) */
function oneam_policy_contact() {
	$lines = array( '<strong>' . esc_html( oneam_opt( 'oneam_company' ) ) . '</strong>' );
	if ( oneam_opt( 'oneam_address' ) ) {
		$lines[] = nl2br( esc_html( trim( oneam_opt( 'oneam_address' ) ) ) );
	}
	$email   = esc_html( oneam_opt( 'oneam_email' ) );
	$lines[] = 'Email: <a href="mailto:' . $email . '">' . $email . '</a>';
	if ( oneam_opt( 'oneam_phone' ) ) {
		$lines[] = 'Phone: ' . esc_html( oneam_opt( 'oneam_phone' ) );
	}
	if ( oneam_licence_line() ) {
		$lines[] = esc_html( oneam_licence_line() );
	}
	if ( oneam_gst_line() ) {
		$lines[] = esc_html( oneam_gst_line() );
	}
	return implode( '<br>', $lines );
}

/** 정책 최종 수정일: 정책 글을 새로 만든 날 (없으면 오늘) */
function oneam_policy_updated() {
	$d = get_option( 'oneam_policy_updated' );
	return esc_html( $d ? $d : wp_date( 'F j, Y' ) );
}

function oneam_policy_vars() {
	$email = esc_html( oneam_opt( 'oneam_email' ) );
	$claim = (int) oneam_opt( 'oneam_claim_days' ) ?: 3;
	return array(
		'co'       => esc_html( oneam_opt( 'oneam_company' ) ),
		'mail'     => '<a href="mailto:' . $email . '">' . $email . '</a>',
		'terms'    => '<a href="' . esc_url( home_url( '/terms/' ) ) . '">Terms &amp; Conditions</a>',
		'returns'  => '<a href="' . esc_url( home_url( '/return-policy/' ) ) . '">Return Policy</a>',
		'ship'     => '<a href="' . esc_url( home_url( '/shipping-policy/' ) ) . '">Shipping Policy</a>',
		'privacy'  => '<a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '">Privacy Policy</a>',
		'updated'  => oneam_policy_updated(),
		'proc'     => esc_html( oneam_opt( 'oneam_processor' ) ?: 'our payment processor' ),
		'pay'      => esc_html( oneam_opt( 'oneam_payments' ) ),
		'contact'  => oneam_policy_contact(),
		'regions'  => esc_html( oneam_regions_phrase() ),
		'dispatch' => esc_html( oneam_opt( 'oneam_dispatch_time' ) ),
		'transit'  => esc_html( oneam_opt( 'oneam_transit_time' ) ),
		'due'      => (int) oneam_opt( 'oneam_pay_due_days' ) ?: 2,
		'claim'    => $claim,
		'defect'   => (int) oneam_opt( 'oneam_defect_days' ) ?: 30,
		'risk'     => esc_html( oneam_opt( 'oneam_risk_transfer' ) ),
		'etr'      => esc_html( function_exists( 'oneam_etransfer_email' ) ? oneam_etransfer_email() : oneam_opt( 'oneam_email' ) ),
		'gst'      => oneam_opt( 'oneam_gst_number' ) ? ' (GST/HST registration no. ' . esc_html( oneam_opt( 'oneam_gst_number' ) ) . ')' : '',
	);
}

/** 문단 여러 개 */
function oneam_ps( $items ) {
	$out = '';
	foreach ( $items as $t ) {
		$out .= oneam_b_p( $t );
	}
	return $out;
}

/* ---------------------------------------------------------------------------
 * Terms & Conditions = Wholesale Terms of Sale + Vaping Product Compliance
 * ------------------------------------------------------------------------- */
function oneam_policy_terms() {
	$v         = oneam_policy_vars();
	$effective = esc_html( oneam_opt( 'oneam_terms_effective' ) ?: $v['updated'] );
	return oneam_b_p( '<em>Effective date: ' . $effective . ' · Last updated: ' . $v['updated'] . '</em>' )
		. oneam_b_h( 'Wholesale Terms of Sale' )
		. oneam_b_h( '1. Seller', 3 )
		. oneam_ps(
			array(
				"These Wholesale Terms of Sale (“Terms”) apply to every account opened and every order placed on this website. The seller is {$v['co']}{$v['gst']} (“we”, “us”), the distributor of 1AM products:",
				$v['contact'],
				"The buyer is the approved retail business that holds the account (“you”). By opening an account or placing an order, you accept these Terms, the Vaping Product Compliance terms below, our {$v['ship']}, {$v['returns']} and {$v['privacy']}.",
			)
		)
		. oneam_b_h( '2. Account eligibility, approval and suspension', 3 )
		. oneam_ps(
			array(
				"We sell only to verified, licensed adult-only vape retail stores in {$v['regions']}, for resale in those stores. We do not sell to consumers. To open an account, you must provide accurate business information and documents; we may ask for more at any time (see Vaping Product Compliance, section 1).",
				'We may approve, refuse, suspend or close an account at our discretion, including if information is inaccurate, documents expire, or a breach of these Terms or of the law is reasonably suspected. You must tell us promptly about any change in your business name, address, ownership or licences, and you are responsible for orders placed through your login.',
			)
		)
		. oneam_b_h( '3. Orders and acceptance', 3 )
		. oneam_ps(
			array(
				'An order placed on this website is an offer to buy. Receiving or acknowledging your order is not acceptance. A contract is formed only when we email you our order confirmation. We may refuse or cancel an order, in whole or in part, including for stock availability, excise stamp availability for your province, pricing errors or failed verification. If we cancel a paid order, we refund the amount paid.',
			)
		)
		. oneam_b_h( '4. Prices and taxes', 3 )
		. oneam_ps(
			array(
				'Prices are in Canadian dollars and are set by province: the price and excise stamp follow the store province selected for the order. Prices exclude GST, which is added at checkout and shown on your invoice' . ( $v['gst'] ? $v['gst'] : '' ) . '.',
				'Provincial sales tax (PST) in British Columbia and Saskatchewan is not charged on purchases for resale when you provide a valid PST number; you are responsible for any PST that applies otherwise. Prices may change without notice; the price shown when you place an order applies to that order.',
			)
		)
		. oneam_b_h( '5. Payment', 3 )
		. oneam_ps(
			array(
				"We accept {$v['pay']}. Send the order total by Interac e-Transfer to {$v['etr']}, with your order number in the message, within <strong>{$v['due']} business days</strong> of placing the order. Orders are processed only after payment is received, and unpaid orders are cancelled automatically after that time.",
			)
		)
		. ( oneam_opt( 'oneam_processor' ) ? oneam_b_p( "Card payments are processed securely by {$v['proc']}. Your card is charged when you place your order. We do not see or store your full card number." ) : '' )
		. oneam_b_h( '6. Shipping, title and risk', 3 )
		. oneam_ps(
			array(
				"We ship only to the licensed store locations registered on your account, within {$v['regions']}, as described in our {$v['ship']}. Title to the products passes to you when we receive payment in full. {$v['risk']}",
			)
		)
		. oneam_b_h( '7. Inspection and claims', 3 )
		. oneam_ps(
			array(
				"Inspect each delivery when it arrives. Report damaged, missing or incorrect items, or stamp errors, within <strong>{$v['claim']} business days of delivery</strong>, with your order number and photos, as set out in our {$v['returns']}. All sales are final, except as set out in the {$v['returns']} or as required by law.",
			)
		)
		. oneam_b_h( '8. Product warranty', 3 )
		. oneam_ps(
			array(
				"We warrant that products are free from manufacturing defects when delivered. If a product is defective and you report it within {$v['defect']} days of delivery, we will replace it or issue a credit, at our option. This is your only remedy for defective products, and we give no other warranties, express or implied, to the extent permitted by law.",
			)
		)
		. oneam_b_h( '9. Limitation of liability', 3 )
		. oneam_ps(
			array(
				'To the extent permitted by law, our total liability for any claim relating to an order is limited to the amount you paid for that order. We are not liable for indirect, incidental or consequential losses, including lost profits.',
			)
		)
		. oneam_b_h( '10. Indemnity', 3 )
		. oneam_ps(
			array(
				'You are responsible for, and will indemnify us against, any claims, fines, penalties and costs arising from your resale, display, promotion or handling of products in breach of these Terms or of any federal, provincial or local law, including age, excise stamp, labelling, display and advertising rules.',
			)
		)
		. oneam_b_h( '11. Governing law', 3 )
		. oneam_ps(
			array(
				'These Terms are governed by the laws of the Province of British Columbia and the federal laws of Canada that apply there. The courts of British Columbia have exclusive jurisdiction over any dispute.',
			)
		)
		. oneam_b_h( '12. General', 3 )
		. oneam_ps(
			array(
				"Our {$v['privacy']} explains how we handle personal information. We may change these Terms by posting a new version on this website; the version posted when you place an order applies to that order, and we will tell account holders by email about material changes. If any part of these Terms is found invalid, the rest remains in effect. These Terms, together with the policies linked above, are the entire agreement between you and us about your orders.",
			)
		)
		. oneam_compliance_blocks()
		. oneam_b_h( 'Health warning' )
		. oneam_b_p( '<strong>WARNING: Vaping products contain nicotine, a highly addictive chemical.</strong> — Health Canada' )
		. oneam_b_h( 'Contact' )
		. oneam_b_p( $v['contact'] );
}

/* ---------------------------------------------------------------------------
 * Return Policy
 * ------------------------------------------------------------------------- */
function oneam_policy_returns() {
	$v = oneam_policy_vars();
	return oneam_b_p( '<em>Last updated: ' . $v['updated'] . '</em>' )
		. oneam_b_h( 'All sales are final' )
		. oneam_ps(
			array(
				'All orders are final sale. We do not accept returns or exchanges for change of mind or overstock. We do resolve damaged, defective and incorrect items, stamp errors and recalls as set out below, and any rights you have under applicable law are not affected.',
			)
		)
		. oneam_b_h( 'Time limits' )
		. oneam_b_list(
			array(
				"Damaged in transit, missing or incorrect items, or wrong excise stamp: report within <strong>{$v['claim']} business days of delivery</strong>.",
				"Manufacturing defects: report within <strong>{$v['defect']} days of delivery</strong>.",
				'Note any visible damage with the carrier when you sign for the delivery, and keep all packaging until your claim is resolved.',
			)
		)
		. oneam_b_h( 'How to make a claim' )
		. oneam_b_list(
			array(
				"Email {$v['mail']} with your order number.",
				'List the products, flavours and quantities affected.',
				'Attach clear photos of the products, the outer carton, the shipping label and the excise stamps.',
				'Wait for our instructions before sending anything back. Unauthorized returns are not accepted.',
			)
		)
		. oneam_b_h( 'How we resolve claims' )
		. oneam_ps(
			array(
				'Approved claims are resolved by replacement, store credit or refund, at our option. Refunds go back to the original payment method (an e-Transfer to the email on your account' . ( oneam_opt( 'oneam_processor' ) ? ', or the same card for card payments' : '' ) . '), usually within 5–10 business days.',
				esc_html( oneam_opt( 'oneam_return_shipping' ) ),
			)
		)
		. oneam_b_h( 'Excise stamp errors' )
		. oneam_ps(
			array(
				"We ship only products stamped for your store's province. If you receive products with the wrong excise stamp for that province, do not sell them. Report it within {$v['claim']} business days of delivery and we will arrange collection and replacement with correctly stamped products at our cost.",
			)
		)
		. oneam_b_h( 'Recalls' )
		. oneam_ps(
			array(
				'If a product is recalled or subject to another safety-related corrective action, we will contact affected retailers with instructions. Stop selling the affected products immediately and follow those instructions. We will replace or credit recalled products, and we cover the cost of returning them.',
			)
		)
		. oneam_b_h( 'Not eligible' )
		. oneam_b_list(
			array(
				'Products that have been opened, used, altered or repackaged, except as needed to identify a defect.',
				'Products with removed, damaged or altered excise stamps, health warnings, labels or regulatory markings (other than a stamp error on our part).',
				'Claims reported after the time limits above.',
			)
		)
		. oneam_b_h( 'Contact' )
		. oneam_b_p( $v['contact'] );
}

/* ---------------------------------------------------------------------------
 * Shipping Policy
 * ------------------------------------------------------------------------- */
function oneam_policy_shipping() {
	$v      = oneam_policy_vars();
	$stamps = array();
	foreach ( oneam_sales_regions() as $c ) {
		$stamps[] = esc_html( ( oneam_provinces()[ $c ] ?? $c ) . ': ' . oneam_stamp_label( $c ) );
	}
	return oneam_b_p( '<em>Last updated: ' . $v['updated'] . '</em>' )
		. oneam_b_h( 'Where we ship' )
		. oneam_ps(
			array(
				"We ship only within <strong>{$v['regions']}</strong>, and only to the licensed retail store locations registered on approved accounts. We do not ship to residential or consumer addresses, to other provinces or territories, or outside Canada.",
				'The shipping address for an order must be a store registered on your account — normally the store address you gave when you applied. If you operate licensed stores in more than one of these provinces, contact us to add them to your account before ordering.',
			)
		)
		. oneam_b_h( 'Excise stamps' )
		. oneam_ps( array( "We ship only products stamped for your store's province. The store province on the order sets the price and the excise stamp:" ) )
		. oneam_b_list( $stamps )
		. oneam_b_h( 'Dispatch and delivery' )
		. oneam_b_list(
			array(
				"Dispatch: orders ship within <strong>{$v['dispatch']}</strong> after payment is received.",
				"Delivery: typically <strong>{$v['transit']}</strong> after shipping, depending on your location. Delivery times are estimates and are not guaranteed.",
				'Carriers: Purolator Ground, UPS Ground or Canpar Ground. A signature from an authorized person at your store may be required.',
				'Shipping charges: ' . esc_html( oneam_shipping_rates_sentence() ),
			)
		)
		. oneam_b_h( 'Tracking, damage and loss' )
		. oneam_ps(
			array(
				"You will receive tracking details by email when your order ships. {$v['risk']}",
				"Inspect deliveries on arrival. Report damaged, missing or incorrect items within <strong>{$v['claim']} business days of delivery</strong>, with your order number and photos — see our {$v['returns']}. If a parcel is delayed or lost in transit, contact us at {$v['mail']} and we will work with the carrier to resolve it.",
			)
		)
		. oneam_b_h( 'Contact' )
		. oneam_b_p( $v['contact'] );
}

/* ---------------------------------------------------------------------------
 * Privacy Policy (PIPEDA · BC PIPA)
 * ------------------------------------------------------------------------- */
function oneam_policy_privacy() {
	$v       = oneam_policy_vars();
	$officer = esc_html( oneam_opt( 'oneam_privacy_officer' ) ?: 'Privacy Officer' );
	$pmail   = esc_html( oneam_opt( 'oneam_privacy_email' ) ?: oneam_opt( 'oneam_email' ) );
	$pmail   = '<a href="mailto:' . $pmail . '">' . $pmail . '</a>';
	$proc    = oneam_opt( 'oneam_processor' ) ? esc_html( oneam_opt( 'oneam_processor' ) ) : 'our card payment processor (when card payments are offered)';
	return oneam_b_p( '<em>Last updated: ' . $v['updated'] . '</em>' )
		. oneam_ps(
			array(
				"{$v['co']} (“we”, “us”) operates this website and the 1AM wholesale program. This policy explains how we collect, use, disclose and protect personal information, in line with Canada’s Personal Information Protection and Electronic Documents Act (PIPEDA) and British Columbia’s Personal Information Protection Act (PIPA).",
			)
		)
		. oneam_b_h( 'Privacy Officer' )
		. oneam_ps( array( "Our {$officer} is responsible for our compliance with this policy. Contact: {$pmail}." ) )
		. oneam_b_h( 'What we collect' )
		. oneam_b_list(
			array(
				'Application details: business name, Business Number, PST number (optional), store address, contact name, email and phone.',
				'Business documents you upload, such as your business licence, business registration or provincial retail authorization.',
				'Order information: products, quantities, store province, billing and shipping details, and payment records (for example, e-Transfer confirmations).',
				'Technical information: IP address, browser type and the cookies described below.',
			)
		)
		. oneam_b_h( 'Why we collect it' )
		. oneam_b_list(
			array(
				'To verify that your business is a licensed adult-only vape retailer eligible to buy from us, and to manage your account.',
				'To process, ship, invoice and support your orders, including matching excise stamps to your store province.',
				'To meet our legal obligations, including tax, excise, record-keeping and product recall requirements.',
				'To contact you about your account, orders and important product or policy updates.',
				'To send marketing emails, only if you have given express consent (the opt-in box on the application form is unchecked by default). You can withdraw consent at any time using the unsubscribe link or by emailing us, in line with Canada’s Anti-Spam Legislation (CASL).',
			)
		)
		. oneam_ps( array( 'We do not sell personal information.' ) )
		. oneam_b_h( 'Who we share it with' )
		. oneam_ps( array( 'We share personal information only as needed with service providers who handle it on our behalf, under contractual safeguards:' ) )
		. oneam_b_list(
			array(
				'Website hosting: GoDaddy.',
				'Email delivery services used to send account and order emails.',
				'Shipping carriers: Purolator, UPS and Canpar.',
				'Payments: Interac e-Transfer through your and our financial institutions, and ' . $proc . '.',
				'Government and regulatory authorities, where required by law.',
			)
		)
		. oneam_ps( array( 'Payment card information, when card payments are offered, is processed by the payment processor. We do not store card numbers on our website or servers.' ) )
		. oneam_b_h( 'Where it is stored' )
		. oneam_ps( array( esc_html( oneam_opt( 'oneam_hosting' ) ) ) )
		. oneam_b_h( 'How long we keep it' )
		. oneam_ps( array( esc_html( oneam_opt( 'oneam_retention' ) ) . ' Uploaded business documents are kept with your account record and deleted when that record is deleted.' ) )
		. oneam_b_h( 'How we protect it' )
		. oneam_ps( array( 'We use reasonable administrative, technical and physical safeguards. The website uses an encrypted (HTTPS) connection, and uploaded documents are stored in a private location that can only be accessed by authorized staff through the admin area.' ) )
		. oneam_b_h( 'Cookies' )
		. oneam_b_list(
			array(
				'Essential cookies to keep you logged in, remember your cart and checkout details, and protect forms from abuse (WordPress and WooCommerce).',
				'A browser setting that remembers you confirmed your age, so the age check is not shown on every page.',
				'We do not use advertising cookies. If we add analytics in future, we will update this policy.',
			)
		)
		. oneam_b_h( 'Your rights' )
		. oneam_ps(
			array(
				"You may ask to see the personal information we hold about you, ask us to correct it, or withdraw your consent (which may mean we can no longer serve your account). Send your request to our {$officer} at {$pmail}. We respond within 30 days. If you are not satisfied with our response, you may contact the Office of the Privacy Commissioner of Canada or the Office of the Information and Privacy Commissioner for British Columbia.",
			)
		)
		. oneam_b_h( 'Changes' )
		. oneam_ps( array( 'We may update this policy. The “last updated” date above shows the latest version.' ) )
		. oneam_b_p( $v['contact'] );
}

/* ---------------------------------------------------------------------------
 * 블록 결제 화면 약관 동의: 체크해야만 주문 + 정책 링크
 * ------------------------------------------------------------------------- */
function oneam_setup_checkout_terms() {
	$id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'checkout' ) : 0;
	if ( $id <= 0 ) {
		return false;
	}
	$post = get_post( $id );
	if ( ! $post || false === strpos( $post->post_content, 'woocommerce/checkout-terms-block' ) ) {
		return false;
	}
	$v    = oneam_policy_vars();
	$text = esc_html( oneam_opt( 'oneam_terms_text' ) );
	$text = str_replace(
		array( 'Terms &amp; Conditions', 'Vaping Product Compliance', 'Privacy Policy', 'Shipping Policy', 'Return Policy' ),
		array( $v['terms'], '<a href="' . esc_url( home_url( '/terms/#vape-compliance' ) ) . '">Vaping Product Compliance</a>', $v['privacy'], $v['ship'], $v['returns'] ),
		$text
	);
	$attrs   = serialize_block_attributes( array( 'checkbox' => true, 'text' => $text ) );
	$content = preg_replace( '/<!-- wp:woocommerce\/checkout-terms-block(\s+\{.*?\})?\s*(\/)?-->/s', '<!-- wp:woocommerce/checkout-terms-block ' . $attrs . ' $2-->', $post->post_content, 1 );
	// 주문 메모 칸을 청구지 주소 바로 아래로 (필수 확인 체크 두 개가 결제 방법 아래에 함께 모이도록)
	$note = '/<!-- wp:woocommerce\/checkout-order-note-block.*?<!-- \/wp:woocommerce\/checkout-order-note-block -->\s*/s';
	$bill = '<!-- /wp:woocommerce/checkout-billing-address-block -->';
	if ( $content && preg_match( $note, $content, $m ) && false !== strpos( $content, $bill ) && strpos( $content, $m[0] ) > strpos( $content, $bill ) ) {
		$content = preg_replace( $note, '', $content, 1 );
		$content = str_replace( $bill, $bill . "\n\n" . trim( $m[0] ), $content );
	}
	if ( $content && $content !== $post->post_content ) {
		wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $content ) ) );
	}
	return true;
}
// 사용자 정의하기에서 문구를 바꾸면 결제 화면에도 반영
add_action( 'customize_save_after', 'oneam_setup_checkout_terms' );
