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

/** 회사 연락처 (카드사 요구: 상호 · 주소 · 이메일 · 전화) */
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
	return implode( '<br>', $lines );
}

function oneam_policy_vars() {
	$email = esc_html( oneam_opt( 'oneam_email' ) );
	return array(
		'co'      => esc_html( oneam_opt( 'oneam_company' ) ),
		'mail'    => '<a href="mailto:' . $email . '">' . $email . '</a>',
		'terms'   => '<a href="' . esc_url( home_url( '/terms/' ) ) . '">Terms &amp; Conditions</a>',
		'returns' => '<a href="' . esc_url( home_url( '/return-policy/' ) ) . '">Return Policy</a>',
		'ship'    => '<a href="' . esc_url( home_url( '/shipping-policy/' ) ) . '">Shipping Policy</a>',
		'privacy' => '<a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '">Privacy Policy</a>',
		'updated' => esc_html( wp_date( 'F j, Y' ) ),
		'proc'    => esc_html( oneam_opt( 'oneam_processor' ) ?: 'our payment processor' ),
		'pay'     => esc_html( oneam_opt( 'oneam_payments' ) ),
		'contact' => oneam_policy_contact(),
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
 * Terms & Conditions
 * ------------------------------------------------------------------------- */
function oneam_policy_terms() {
	$v = oneam_policy_vars();
	return oneam_b_p( '<em>Last updated: ' . $v['updated'] . '</em>' )
		. oneam_ps(
			array(
				"These Terms &amp; Conditions govern all use of this website and all orders placed with {$v['co']} (“we”, “us”), the distributor of 1AM products. This website is a wholesale platform for verified business customers only. By creating an account or placing an order, you (“the purchaser”) agree to these terms, our {$v['ship']}, {$v['returns']} and {$v['privacy']}.",
			)
		)
		. oneam_b_h( 'Wholesale only — no consumer sales' )
		. oneam_ps(
			array(
				'We sell exclusively to verified and approved adult-only vape retail businesses in Canada. We do not sell to individual consumers, and products purchased from us are for resale in the purchaser’s licensed retail business only.',
				'You must be of legal age in your province or territory to use this website. Wholesale pricing and ordering are visible only to approved accounts.',
			)
		)
		. oneam_b_h( 'Accounts and verification' )
		. oneam_ps(
			array(
				'To open an account, you must provide accurate business information and supporting documents confirming that your business is an authorized adult-only vape retailer. We may request additional documents at any time, before or after approval.',
				'You are responsible for keeping your login details secure and for all orders placed through your account. You must tell us promptly about any change that affects your eligibility, including changes to your business name, address, ownership or licences.',
				'We may approve, refuse, suspend or close an account at our discretion, including where information is inaccurate or a breach of these terms or applicable law is reasonably suspected.',
			)
		)
		. oneam_compliance_blocks()
		. oneam_b_h( 'Orders' )
		. oneam_ps(
			array(
				'An order placed on this website is an offer to purchase. A contract is formed only when we confirm and accept the order. We may refuse, limit or cancel any order, including for product availability, pricing errors, regulatory reasons or failed verification.',
				'Minimum order quantities and units (for example, cartons) are shown on each product. Before placing an order, you must confirm the Excise Stamp &amp; Regulatory Compliance statement at checkout.',
			)
		)
		. oneam_b_h( 'Prices and taxes' )
		. oneam_ps(
			array(
				'Prices are in Canadian dollars and are set by province, based on the shipping (sales) province selected at checkout. Prices exclude GST and any other applicable taxes, which are added at checkout or on your invoice.',
				'Products are available only in the provinces listed on each product. If an obvious pricing or product error occurs, we may cancel the affected order or contact you to confirm the correct price before shipping.',
			)
		)
		. oneam_b_h( 'Payment' )
		. oneam_ps(
			array(
				"We accept {$v['pay']}. All transactions are in Canadian dollars (CAD).",
				"Card payments are processed securely by {$v['proc']}. Your card is charged when you place your order. We never see or store your full card number; card details are encrypted and handled by {$v['proc']} in line with payment card industry (PCI DSS) security standards. This website uses an encrypted (SSL/TLS) connection.",
				'Orders paid by Interac e-Transfer or bank transfer are processed once payment is received. Unpaid orders may be cancelled after 3 business days.',
				'Title to products passes to you when payment is received in full.',
			)
		)
		. oneam_b_h( 'Order changes and cancellations' )
		. oneam_ps(
			array(
				"To change or cancel an order, contact us as soon as possible at {$v['mail']}. Orders can be cancelled only before they are processed for shipping. If a paid order is cancelled, the amount is refunded to the original payment method.",
			)
		)
		. oneam_b_h( 'Refunds and payment disputes' )
		. oneam_ps(
			array(
				"Approved refunds are issued to the original payment method — card refunds go back to the same card — usually within 5–10 business days, depending on your card issuer. Before disputing a charge with your card issuer, please contact us so we can resolve the issue quickly. See our {$v['returns']}.",
			)
		)
		. oneam_b_h( 'Shipping and delivery' )
		. oneam_ps(
			array(
				"We ship only to the verified business address on your account, within the provinces where the products are offered for sale. Delivery estimates are not guaranteed. Risk of loss passes to you on delivery. See our {$v['ship']} for details.",
			)
		)
		. oneam_b_h( 'Returns and claims' )
		. oneam_ps(
			array(
				"All sales are final, except as required by law or as set out in our {$v['returns']}, which explains how to report damaged, defective or incorrect items.",
			)
		)
		. oneam_b_h( 'Product information and health warning' )
		. oneam_ps(
			array(
				'Product images, colours and flavour descriptions are for identification only. Flavour descriptions describe taste characteristics and are not health, safety or therapeutic claims. We make no claim that our products help people quit smoking or are safer than any other product.',
				'<strong>WARNING: Vaping products contain nicotine, a highly addictive chemical.</strong> Keep out of reach of children and pets.',
			)
		)
		. oneam_b_h( 'Marketing materials and trademarks' )
		. oneam_ps(
			array(
				'1AM names, logos and images are our property or used under licence. Approved retailers may use product images we provide only to identify products for sale in their store, and only in a way that complies with federal and provincial rules on vaping product promotion. You may not use our materials in advertising that appeals to young people, uses testimonials or endorsements, or associates vaping with a lifestyle.',
			)
		)
		. oneam_b_h( 'Limitation of liability' )
		. oneam_ps(
			array(
				'To the extent permitted by law, our total liability for any claim relating to an order is limited to the amount you paid for the products in that order. We are not liable for indirect or consequential losses, including lost profits, or for losses caused by a purchaser’s failure to comply with applicable laws.',
			)
		)
		. oneam_b_h( 'Indemnity' )
		. oneam_ps(
			array(
				'You agree to indemnify us against claims, penalties and costs arising from your resale of products in breach of these terms or of any federal, provincial, territorial or local law, including excise stamp, age, labelling, display and advertising requirements.',
			)
		)
		. oneam_b_h( 'Changes, governing law and contact' )
		. oneam_ps(
			array(
				'We may update these terms at any time. The version posted on this website when you place an order applies to that order.',
				'These terms are governed by the laws of the Province of British Columbia and the federal laws of Canada that apply there.',
				'Questions? Contact us:',
			)
		)
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
				'All orders are final sale. We do not accept returns or exchanges, except as required by applicable law or as set out below.',
				"If you experience any issue with your order, please contact our customer service team at {$v['mail']}. We will do our best to address your concerns and find a satisfactory resolution.",
			)
		)
		. oneam_b_h( 'Damaged or incorrect items' )
		. oneam_ps(
			array(
				'Please inspect your shipment on delivery. Report damaged cartons or incorrect items within <strong>3 business days of delivery</strong>.',
				'Note any visible damage with the carrier when you sign for the delivery, and keep all packaging until your claim is resolved.',
			)
		)
		. oneam_b_h( 'Defective products' )
		. oneam_ps(
			array(
				'Report a manufacturing defect within <strong>30 days of delivery</strong>. Do not open, use or alter more product than needed to identify the defect.',
			)
		)
		. oneam_b_h( 'How to make a claim' )
		. oneam_b_list(
			array(
				"Email {$v['mail']} with your order number.",
				'List the products, flavours and quantities affected.',
				'Attach clear photos of the products, the outer carton, the shipping label and the excise stamps.',
				'Wait for our instructions before sending anything back. Unauthorized returns will not be accepted.',
			)
		)
		. oneam_ps(
			array(
				'Approved claims are resolved by replacement, store credit or refund, at our discretion. Refunds are issued to the original payment method — card refunds go back to the same card, usually within 5–10 business days depending on your card issuer.',
			)
		)
		. oneam_b_h( 'Not eligible' )
		. oneam_b_list(
			array(
				'Products that have been opened, used, altered or repackaged, except as needed to identify a defect.',
				'Products with removed, damaged or altered excise stamps, health warnings, labels or regulatory markings.',
				'Products that were ordered for the wrong province or excise stamp jurisdiction.',
				'Claims reported after the time limits above.',
			)
		)
		. oneam_b_h( 'Recalls' )
		. oneam_ps(
			array(
				'If a product is subject to a recall or other safety-related corrective action, we will contact affected retailers with instructions. Stop selling the affected products immediately and follow those instructions.',
			)
		)
		. oneam_b_h( 'Contact' )
		. oneam_b_p( $v['contact'] );
}

/* ---------------------------------------------------------------------------
 * Shipping Policy
 * ------------------------------------------------------------------------- */
function oneam_policy_shipping() {
	$v       = oneam_policy_vars();
	$regions = array();
	foreach ( oneam_get_products() as $p ) {
		$regions = array_merge( $regions, (array) ( $p['regions'] ?? array() ) );
	}
	$names = array();
	foreach ( array_unique( $regions ) as $code ) {
		$names[] = oneam_provinces()[ $code ] ?? $code;
	}
	$where = $names ? implode( ', ', $names ) : 'the provinces listed on each product';
	return oneam_b_p( '<em>Last updated: ' . $v['updated'] . '</em>' )
		. oneam_b_h( 'Where we ship' )
		. oneam_ps(
			array(
				"We ship only to verified business addresses of approved retailers, within the provinces where each product is offered for sale. Products are currently available in: <strong>{$where}</strong>.",
				'Each carton carries the provincial excise stamp for its sales jurisdiction. The shipping province you choose at checkout determines the price and the excise stamps on your order. We cannot ship to residential or consumer addresses, or outside the listed provinces.',
			)
		)
		. oneam_b_h( 'Processing and delivery' )
		. oneam_b_list(
			array(
				'Orders are processed after payment is received, usually within 1–2 business days.',
				'We ship by Purolator Ground, UPS Ground or Canpar Ground.',
				'Estimated delivery is 1–5 business days after shipping, depending on your location. Delivery times are estimates and are not guaranteed.',
				'A signature from an authorized person at your business may be required on delivery.',
			)
		)
		. oneam_b_h( 'Tracking, delivery issues and risk' )
		. oneam_ps(
			array(
				"You will receive tracking details by email once your order ships. Risk of loss passes to you when the order is delivered. Please inspect deliveries on arrival and report damage within 3 business days — see our {$v['returns']}.",
				"If a parcel is delayed or lost in transit, contact us at {$v['mail']} and we will work with the carrier to resolve it.",
			)
		)
		. oneam_b_h( 'Contact' )
		. oneam_b_p( $v['contact'] );
}

/* ---------------------------------------------------------------------------
 * Privacy Policy (캐나다 PIPEDA 기준 기본)
 * ------------------------------------------------------------------------- */
function oneam_policy_privacy() {
	$v = oneam_policy_vars();
	return oneam_b_p( '<em>Last updated: ' . $v['updated'] . '</em>' )
		. oneam_ps(
			array(
				"{$v['co']} respects your privacy. This policy explains how we collect, use and protect personal information through this website, in line with Canada’s Personal Information Protection and Electronic Documents Act (PIPEDA) and applicable provincial privacy laws.",
			)
		)
		. oneam_b_h( 'What we collect' )
		. oneam_b_list(
			array(
				'Business and contact details you provide when applying: business name, business number, store address, contact name, email and phone.',
				'Business documents you upload to verify your retail account.',
				'Order, payment, billing and shipping information.',
				'Basic technical information such as IP address, browser and cookies needed to run the website, keep you logged in and remember your cart.',
			)
		)
		. oneam_b_h( 'How we use it' )
		. oneam_b_list(
			array(
				'To verify that your business is an authorized adult-only vape retailer and to manage your account.',
				'To process, ship and support your orders, and to meet tax, excise and regulatory obligations, including recalls.',
				'To contact you about your account, orders and important product or policy updates.',
			)
		)
		. oneam_ps( array( 'We do not sell your personal information.' ) )
		. oneam_b_h( 'Sharing' )
		. oneam_ps(
			array(
				"We share information only as needed with service providers who help us run our business — for example, our payment processor ({$v['proc']}), shipping carriers, website hosting and email services — and with government authorities where required by law.",
				"Card payments are processed by {$v['proc']}. Card numbers are entered into {$v['proc']}’s secure payment form and are not stored on our website or servers.",
			)
		)
		. oneam_b_h( 'Storage, security and retention' )
		. oneam_ps(
			array(
				'We protect your information with reasonable safeguards. Uploaded business documents are stored privately and are accessible only to authorized staff. We keep records for as long as needed for the purposes above and to meet legal, tax and regulatory record-keeping requirements.',
			)
		)
		. oneam_b_h( 'Your choices and contact' )
		. oneam_ps(
			array(
				"You may request access to or correction of your personal information, or withdraw consent where applicable (which may limit our ability to serve your account). Contact our privacy contact at {$v['mail']}.",
			)
		)
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
		array( 'Terms &amp; Conditions', 'Shipping Policy', 'Return Policy' ),
		array( $v['terms'], $v['ship'], $v['returns'] ),
		$text
	);
	$attrs   = serialize_block_attributes( array( 'checkbox' => true, 'text' => $text ) );
	$content = preg_replace( '/<!-- wp:woocommerce\/checkout-terms-block(\s+\{.*?\})?\s*(\/)?-->/s', '<!-- wp:woocommerce/checkout-terms-block ' . $attrs . ' $2-->', $post->post_content, 1 );
	if ( $content && $content !== $post->post_content ) {
		wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $content ) ) );
	}
	return true;
}
// 사용자 정의하기에서 문구를 바꾸면 결제 화면에도 반영
add_action( 'customize_save_after', 'oneam_setup_checkout_terms' );
