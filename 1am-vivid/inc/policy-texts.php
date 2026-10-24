<?php
/**
 * 정책 원문 (대표님이 보내준 글, 2026-10-24) — 그대로 보관하고 페이지 블록으로 바꿔서 사용
 *
 * 원문에서 바꾼 곳 (합의된 내용):
 *  - [Publication Date] → 정책을 새로 만든 날짜
 *  - 약관 3조 카드 결제 문구: 카드 결제사(Moneris)를 설정하기 전까지는 e-Transfer 만
 *  - 서류: 가입 때 서류를 받지 않으므로 "필요하면 요청할 수 있음"으로 (약관 1조 · 개인정보 1조)
 * 규칙: "1. 제목" = 큰 제목 · "* " = 목록 · 마침표 없는 짧은 줄 = 작은 제목 · "VANSKAI TRADING LTD." 부터 끝까지 = 연락처
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oneam_policy_raw( $which ) {
	$cards = oneam_opt( 'oneam_processor' )
		? 'We accept Interac e-Transfer, Visa, Mastercard and American Express.'
		: 'We accept Interac e-Transfer. Card payments (Visa, Mastercard and American Express) will be added when available.';

	$texts = array();

	$texts['privacy'] = <<<'TXT'
Last updated: [Publication Date]

VANSKAI TRADING LTD. (“we”, “us”, “our”) operates the 1AM website and retailer account program. We respect your privacy and handle personal information in accordance with applicable Canadian privacy laws, including British Columbia’s Personal Information Protection Act (PIPA) and the federal Personal Information Protection and Electronic Documents Act (PIPEDA), where applicable.

1. Information We Collect

We collect information necessary to operate our business, including:

* Business information: Business name, registration details, store address, contact information and any verification documents we request.
* Order information: Products ordered, billing and shipping details, payment records and invoices.
* Website information: IP addresses, browser information and essential cookies.

2. How We Use Your Information

We use your information to:

* Verify and approve retailer accounts.
* Process orders, payments and deliveries.
* Confirm applicable excise stamping requirements.
* Provide customer support and account updates.
* Meet tax, regulatory and record-keeping obligations.

We may send promotional emails with your consent or as otherwise permitted by Canada’s Anti-Spam Legislation (CASL). You can unsubscribe at any time.

We do not sell personal information.

3. Sharing Your Information

We share information only when necessary with trusted service providers, including:

* Website hosting: GoDaddy.
* Shipping: Purolator, UPS and Canpar.
* Payments: Financial institutions for Interac e-Transfers and Moneris for card payments when available.
* Business operations: Account management, email and accounting service providers.

We may also disclose information to government authorities or legal representatives when required or permitted by law.

Some service providers may process or store information outside Canada, including in the United States, where different privacy laws may apply.

4. Data Retention and Security

We retain information only as long as reasonably necessary or legally required.

* Account records: While active and generally up to 2 years after closure.
* Rejected applications: Generally 12 months after the decision.
* Financial and tax records: Generally at least 6 years, or longer where legally required.

Longer retention periods may apply where necessary to meet legal obligations.

We use reasonable security measures, including encrypted website connections and restricted access to customer information.

5. Cookies

Our website uses essential cookies and similar technologies for login sessions, shopping carts, security and remembering age-verification acknowledgment.

We do not currently use advertising cookies. Any future changes to tracking practices will be reflected in this policy.

6. Your Privacy Rights

You may request access to your personal information, ask for corrections or withdraw consent, subject to applicable legal requirements.

To make a request, contact our Privacy Officer at sales@1amvape.com.

We will respond within the timeframe required by applicable privacy law.

If you are dissatisfied with our response, you may contact the Office of the Information and Privacy Commissioner for British Columbia or the Office of the Privacy Commissioner of Canada.

7. Policy Updates and Contact

We may update this Privacy Policy as our practices or legal requirements change. The date above indicates the latest revision.

VANSKAI TRADING LTD.
Email: sales@1amvape.com
Vaping Product Licence No. 52-VAL-288
GST/HST Reg. No. 767692510 RT0001
TXT;

	$texts['shipping'] = <<<'TXT'
Last updated: [Publication Date]

1. Shipping Eligibility

We ship exclusively to approved retail store locations in the Canadian provinces we serve.

Orders must be shipped to a registered store address associated with your approved retailer account. We do not ship to residential addresses, individual consumers or locations outside Canada.

2. Shipping Charges

* Free Shipping: Orders of $1,000 CAD or more (before tax) qualify for free ground shipping.
* Orders Under $1,000: Please contact us at sales@1amvape.com to arrange your order. Shipping charges will apply.

3. Processing & Delivery

* Processing Time: Orders typically ship within 1–3 business days after payment confirmation.
* Delivery Time: Typically 1–5 business days after dispatch, depending on location.
* Carriers: Purolator Ground, UPS Ground or Canpar Ground.

Delivery times are estimates and are not guaranteed. A signature may be required upon delivery.

4. Excise Stamp Compliance

All vaping products are supplied with the appropriate excise stamp for their intended province or territory of retail sale.

Retailers are responsible for confirming that the products ordered carry the correct excise stamps for their intended sales location.

5. Damaged, Missing or Lost Shipments

Tracking information will be emailed once your order ships. Risk of loss transfers to the retailer upon delivery.

Please inspect your shipment upon arrival and report any damaged, missing or incorrect items within 3 business days of delivery.

For lost or delayed shipments, contact us and we will work with the carrier to resolve the issue.

For additional details, please refer to our Return Policy.

6. Contact

For shipping questions or assistance, contact:

VANSKAI TRADING LTD.
Email: sales@1amvape.com
Vaping Product Licence No. 52-VAL-288
GST/HST Reg. No. 767692510 RT0001
TXT;

	$texts['returns'] = <<<'TXT'
Last updated: [Publication Date]

1. All Sales Are Final

All orders are final sale. We do not accept returns or exchanges for change of mind, overstock or unsold inventory.

Exceptions may apply to damaged, defective or incorrectly shipped products, excise stamp errors caused by us, and product recalls. Your rights under applicable law remain unaffected.

2. Damaged, Defective or Incorrect Items

Please report any issues within the following timeframes:

* Damaged, missing or incorrect items: Within 3 business days of delivery.
* Manufacturing defects: Within 30 days of delivery.

To submit a claim, email sales@1amvape.com with your order number, a description of the issue and supporting photos.

Claims are subject to review and approval.

3. Excise Stamp Errors

If the excise stamp on a delivered product does not match your confirmed order, do not sell the product. Contact us within 3 business days of delivery.

If the error is ours, we will arrange a replacement at our expense.

Retailers are responsible for selecting the correct excise stamps for their intended province or territory of retail sale. Incorrect selections made by the retailer are not eligible for return or exchange.

4. Refunds & Replacements

Approved claims will be resolved through replacement, store credit or refund, at our discretion, subject to applicable law.

Refunds are generally processed within 5–10 business days after approval.

We cover authorized return shipping costs for confirmed defects or errors on our part.

Do not return any products without our prior authorization and shipping instructions. Special handling may be required for products containing lithium batteries.

5. Product Recalls

In the event of a product recall or safety-related corrective action, we will notify affected retailers and provide instructions.

Retailers must immediately stop selling affected products and follow the provided instructions.

Replacements, credits and return arrangements will be handled according to the applicable recall requirements.

6. Contact

For return requests or assistance, contact:

VANSKAI TRADING LTD.
Email: sales@1amvape.com
Vaping Product Licence No. 52-VAL-288
GST/HST Reg. No. 767692510 RT0001
TXT;

	$texts['terms'] = <<<'TXT'
Last updated: [Publication Date]

These Terms & Conditions (“Terms”) govern all retailer accounts and orders placed through the 1AM website, operated by VANSKAI TRADING LTD. (“we”, “us”, “our”).

By applying for an account or placing an order, you agree to these Terms, including the Vaping Product Compliance requirements, our Shipping Policy and Return Policy. Our Privacy Policy explains how we handle personal information.

1. Retailer Eligibility & Accounts

* We sell exclusively to verified and approved Canadian retailers. We do not sell directly to consumers.
* Retailers must hold all required business licences, registrations and authorizations to purchase and resell vaping products.
* Accurate business information must be provided during registration and kept up to date. We may request supporting documents at any time.
* We reserve the right to approve, refuse, suspend or terminate accounts for compliance, security or suspected violations of applicable laws or these Terms.

Account holders are responsible for all orders placed through their accounts.

2. Orders, Pricing & Taxes

* All prices are in Canadian dollars (CAD) and exclude applicable sales taxes.
* Applicable GST/HST is charged according to Canadian tax laws. PST is not charged on qualifying purchases for resale when the required exemption information is provided.
* Pricing may vary depending on applicable excise stamp requirements.
* Prices and product availability may change without notice.

An order placed through our website is an offer to purchase. An automatic order receipt does not constitute acceptance. Orders are confirmed after payment, stock availability and retailer eligibility have been verified.

We may refuse or cancel orders due to product availability, pricing errors or compliance concerns. Payments received for cancelled orders will be refunded.

3. Payment Terms

{{CARDS}}

Full payment is required before orders are processed and shipped.

For Interac e-Transfer payments, send payment to sales@1amvape.com, including your order number. Orders will be processed once payment is received and confirmed.

Payment Security: We will not change our payment details or request payment to a different address through unsolicited emails or text messages. Please contact us directly if you receive a suspicious payment request.

4. Shipping & Delivery

* Free ground shipping applies to orders of $1,000 CAD or more before tax.
* For orders under $1,000, please contact us to arrange your order. Shipping charges will apply.
* We ship only to approved retail store locations registered on your account, within the Canadian provinces we serve.

Delivery times are estimates and are not guaranteed.

Ownership of products transfers upon full payment. Risk of loss transfers upon delivery.

Please refer to our Shipping Policy for processing times, delivery details and shipping conditions.

5. Vaping Product Compliance

Authorized Retailers

All products are supplied exclusively to approved retailers for lawful resale. Retailers must maintain all required licences and authorizations and comply with applicable federal, provincial, territorial and local vaping regulations.

Excise Stamp Compliance

All vaping products are supplied with the appropriate excise stamp for their intended province or territory of retail sale.

By placing an order, retailers confirm that:

* The selected excise stamps match the intended retail sales jurisdiction.
* Products will be sold only in jurisdictions where their excise stamps are valid.
* Products will not be transferred, redistributed or resold in violation of applicable laws.

Retailers are responsible for confirming the intended sales jurisdiction and selected excise stamps before completing an order.

Any proposed distribution to additional store locations or jurisdictions must comply with applicable laws and our retailer account requirements.

If we ship products with excise stamps that do not match the confirmed order, we will resolve the error under our Return Policy.

Product Integrity & Retail Compliance

Retailers must comply with all applicable age restrictions, product reporting, display, advertising and sales requirements.

Original product packaging, nicotine contents, health warnings, regulatory labels and excise stamps must not be unlawfully altered, removed or tampered with.

Product Safety & Recalls

Retailers must promptly report known product defects or safety concerns. In the event of a recall, affected products must be removed from sale immediately and all applicable recall instructions followed.

Nothing in this section limits VANSKAI TRADING LTD.’s own obligations under applicable law.

6. Returns & Claims

All sales are final. Returns or exchanges are not accepted for change of mind, overstock or unsold inventory.

Exceptions apply as outlined in our Return Policy.

* Damaged, missing or incorrectly shipped items: Report within 3 business days of delivery.
* Manufacturing defects: Report within 30 days of delivery.
* Excise stamp errors caused by us: Report within 3 business days of delivery.

Approved claims may be resolved through replacement, store credit or refund, at our discretion, subject to applicable law.

Products must not be returned without prior authorization.

Please refer to our Return Policy for full details.

7. Product Warranty

We warrant that products are free from manufacturing defects at the time of delivery.

For manufacturing defects reported within 30 days of delivery, our sole contractual obligation is to provide a replacement, store credit or refund, at our discretion.

To the fullest extent permitted by law, this limited warranty replaces all other warranties or conditions, express or implied, including implied warranties or conditions of merchantable quality, durability and fitness for a particular purpose.

Nothing in this section excludes any warranty, condition or remedy that cannot legally be excluded.

8. Limitation of Liability

To the extent permitted by law, our total liability for any claim relating to an order is limited to the price of that order.

We are not liable for indirect, incidental or consequential losses, including lost profits, except where liability cannot legally be excluded.

9. Retailer Indemnification

Retailers agree to indemnify VANSKAI TRADING LTD. against claims, losses, damages, penalties and reasonable legal costs arising from their unlawful resale, distribution, handling, display or promotion of products, or breaches of these Terms, to the extent permitted by law.

This does not apply to losses caused by our own unlawful conduct or obligations that cannot legally be transferred.

10. Events Beyond Our Control & Changes in Law

We are not responsible for delays or failures caused by circumstances beyond our reasonable control, including carrier disruptions, supply shortages, labour disputes, natural disasters or government actions.

If changes in laws or regulations affect product availability or lawful sale, we may modify or cancel unshipped orders and refund payments for cancelled items.

Products already delivered are not eligible for return solely due to subsequent regulatory changes, except for recalls or where required by law.

11. Governing Law & Jurisdiction

These Terms are governed by the laws of British Columbia and the applicable federal laws of Canada.

The courts and tribunals of British Columbia, including the Civil Resolution Tribunal where it has jurisdiction, shall have exclusive jurisdiction over disputes arising from these Terms, subject to any mandatory legal requirements.

12. Acceptance & Policy Updates

Retailers must accept these Terms by selecting the agreement checkbox when applying for an account and when placing an order.

We maintain records of the date and version of the Terms accepted.

We may update these Terms by posting a revised version on our website. Material changes will be communicated to account holders by email. The version accepted when an order is placed applies to that order.

These Terms, together with our Shipping Policy and Return Policy, constitute the agreement governing your orders.

If any provision is found unenforceable, the remaining provisions remain in effect.

13. Contact

VANSKAI TRADING LTD.
Email: sales@1amvape.com
Vaping Product Licence No. 52-VAL-288
GST/HST Reg. No. 767692510 RT0001
TXT;

	$raw = $texts[ $which ] ?? '';
	return str_replace( array( '[Publication Date]', '{{CARDS}}' ), array( oneam_policy_updated(), $cards ), $raw );
}

/** 원문 → 블록 편집기 글 (제목 · 목록 · 문단 · 링크) */
function oneam_policy_blocks( $which ) {
	$email = oneam_opt( 'oneam_email' );
	$links = array(
		'Shipping Policy' => home_url( '/shipping-policy/' ),
		'Return Policy'   => home_url( '/return-policy/' ),
		'Privacy Policy'  => home_url( '/privacy-policy/' ),
	);
	$fmt = function ( $t ) use ( $email, $links ) {
		$t = esc_html( $t );
		$t = str_replace( esc_html( $email ), '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>', $t );
		foreach ( $links as $label => $url ) {
			$t = preg_replace( '/(?<![\w>])our ' . preg_quote( $label, '/' ) . '/', 'our <a href="' . esc_url( $url ) . '">' . $label . '</a>', $t );
		}
		// "Label: text" → 굵은 라벨
		return preg_replace( '/^([A-Z][^:.]{1,40}):\s/', '<strong>$1:</strong> ', $t );
	};

	$out     = '';
	$list    = array();
	$contact = array();
	$flush   = function () use ( &$list, &$out ) {
		if ( $list ) {
			$out .= oneam_b_list( $list );
			$list = array();
		}
	};
	$lines = preg_split( '/\r?\n/', oneam_policy_raw( $which ) );
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( $contact || 'VANSKAI TRADING LTD.' === $line ) {
			if ( '' !== $line ) {
				$contact[] = 'VANSKAI TRADING LTD.' === $line ? '<strong>' . esc_html( $line ) . '</strong>' : $fmt( $line );
			}
			continue;
		}
		if ( '' === $line ) {
			continue;
		}
		if ( 0 === strpos( $line, '* ' ) ) {
			$list[] = $fmt( substr( $line, 2 ) );
			continue;
		}
		$flush();
		if ( 0 === strpos( $line, 'Last updated:' ) ) {
			$out .= oneam_b_p( '<em>' . esc_html( $line ) . '</em>' );
		} elseif ( preg_match( '/^\d+\.\s+\S/', $line ) ) {
			$out .= oneam_b_h( esc_html( $line ), 2, false !== stripos( $line, 'Vaping Product Compliance' ) ? 'vape-compliance' : '' );
		} elseif ( strlen( $line ) < 60 && ! preg_match( '/[.:!?]$/', $line ) ) {
			$out .= oneam_b_h( esc_html( $line ), 3 );
		} else {
			$out .= oneam_b_p( $fmt( $line ) );
		}
	}
	$flush();
	if ( $contact ) {
		$out .= oneam_b_p( implode( '<br>', $contact ) );
	}
	return $out;
}

/** 약관 버전 = 정책을 마지막으로 만든 날짜 (가입 · 주문 때 함께 기록) */
function oneam_terms_version() {
	return 'Terms ' . oneam_policy_updated();
}
