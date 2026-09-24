<?php

namespace App\Services;

class MarketplacePolicyService
{
    public const LAST_UPDATED = 'September 15, 2026';

    public static function all(): array
    {
        return [
            'terms' => [
                'title' => 'Terms and Conditions',
                'summary' => 'The rules that govern accounts, marketplace transactions, content, and use of Ani-Mart.',
                'icon' => 'bi-file-earmark-check',
                'sections' => [
                    ['title' => '1. Acceptance and eligibility', 'paragraphs' => ['By creating an account, opening a shop, placing an order, or otherwise using Ani-Mart, you agree to these terms and the policies linked from this Policy Center. You must have legal capacity to enter into transactions or use the service with the involvement and permission of a parent or lawful guardian.']],
                    ['title' => '2. Ani-Mart’s role', 'paragraphs' => ['Ani-Mart provides technology that helps customers discover products and transact with independent sellers. Sellers are responsible for their listings, product quality, legal compliance, fulfilment, and representations. Ani-Mart may moderate listings, preserve transaction records, restrict accounts, and assist with complaints, returns, and safety concerns.']],
                    ['title' => '3. Accounts and verification', 'bullets' => ['Provide accurate, current information and keep credentials confidential.', 'Do not impersonate another person, create deceptive accounts, or evade a restriction.', 'Seller badges and verification signals are informational and do not replace a customer’s own judgment.', 'Notify Ani-Mart promptly if you believe an account has been compromised.']],
                    ['title' => '4. Orders and payment', 'paragraphs' => ['An order is submitted when checkout is completed. A seller may accept it and begin preparation. Available payment methods are shown at checkout. Prices, quantities, delivery fees, payment method, and order status are retained as transaction records.']],
                    ['title' => '5. Cash on Delivery (COD)', 'paragraphs' => ['Cash on Delivery means the customer pays the recorded order total in cash when the order is delivered by the seller or authorized delivery person. COD availability is shown at checkout and may depend on the delivery location, order status, and account eligibility.'], 'bullets' => ['Customers must provide an accurate delivery address and contact details, be available during the agreed delivery schedule, and prepare the amount shown in the final order total.', 'Sellers and delivery personnel must not collect more than the recorded order total unless a customer-approved order change or charge is documented in Ani-Mart.', 'Payment and delivery confirmation must be recorded accurately. Customers should request or retain the available order or payment record.', 'If delivery cannot be completed or the customer refuses an accurate, undamaged order without a valid reason, the attempt may be recorded as failed or cancelled. Repeated unjustified COD refusals may result in limits on COD use after appropriate review.', 'Paying through COD or confirming receipt does not waive applicable cancellation, return, refund, complaint, or consumer-protection rights. Any later cash refund must be recorded through Ani-Mart.']],
                    ['title' => '6. User content and acceptable use', 'bullets' => ['Only upload content you have the right to use.', 'Reviews, messages, evidence, and listings must be truthful and relevant.', 'Do not disrupt the service, scrape protected data, spread malware, manipulate reviews, or harass other users.', 'Do not use Ani-Mart to sell or request prohibited products.']],
                    ['title' => '7. Enforcement and service changes', 'paragraphs' => ['Ani-Mart may remove content, pause listings, restrict functionality, suspend accounts, preserve evidence, or refer matters to authorities when reasonably necessary for safety, legal compliance, or enforcement. Features and these terms may change; material updates will be posted with a revised date. Continued use after an update means the updated terms apply to later activity.']],
                    ['title' => '8. Disputes and responsibility', 'paragraphs' => ['Use the order, return, complaint, and messaging tools to create a clear record of an issue. Nothing in these terms excludes rights or remedies that cannot lawfully be excluded under applicable Philippine law. If a policy conflicts with mandatory law, the mandatory law controls.']],
                ],
                'sources' => [['label' => 'DTI eCommerce laws and policy issuances', 'url' => 'https://ecommerce.dti.gov.ph/related-laws-policy-issuance/']],
            ],
            'privacy' => [
                'title' => 'Privacy Policy',
                'summary' => 'How Ani-Mart collects, uses, shares, retains, and protects personal information.',
                'icon' => 'bi-shield-lock',
                'sections' => [
                    ['title' => '1. Information we process', 'bullets' => ['Account details such as name, email, phone number, role, and authentication records.', 'Seller application, shop, identity-verification, farm, and product information.', 'Order, cart, delivery address, approximate or precise delivery location when provided, payment method, and transaction history.', 'Messages, reviews, reports, complaints, cancellation and return reasons, uploaded evidence, and support communications.', 'Security, device, session, audit, and usage records needed to operate and protect the service.']],
                    ['title' => '2. Why we use information', 'bullets' => ['Create accounts and provide marketplace functions.', 'Process orders, deliveries, cancellations, returns, refunds, reviews, and disputes.', 'Verify sellers, moderate products, prevent fraud and abuse, and protect marketplace safety.', 'Send service, order, inventory, security, and policy notifications.', 'Measure performance, improve features, keep financial and audit records, and comply with lawful obligations.']],
                    ['title' => '3. When information is shared', 'paragraphs' => ['Information is shared only as reasonably necessary: between the customer and seller for an order; with infrastructure, storage, email, image-hosting, and security providers acting for Ani-Mart; with administrators reviewing verification or safety matters; or with authorities and other parties when required by law or necessary to protect rights and safety. Ani-Mart does not authorize sellers to use order information for unrelated marketing.']],
                    ['title' => '4. Retention and security', 'paragraphs' => ['Ani-Mart retains information for as long as needed to provide the service, maintain transaction and resolution history, satisfy legal or accounting needs, resolve disputes, and protect the platform. Retention may differ by record type. Reasonable organizational, physical, and technical safeguards are used, but no system can guarantee absolute security.']],
                    ['title' => '5. Your privacy rights', 'bullets' => ['Be informed about processing and request access to personal information.', 'Request correction of inaccurate or incomplete information.', 'Object to or withdraw consent from processing that relies on consent, subject to lawful limitations.', 'Request erasure or blocking where the legal conditions are met.', 'Request data portability where applicable and lodge a complaint with the National Privacy Commission.']],
                    ['title' => '6. Cookies and account controls', 'paragraphs' => ['Ani-Mart uses essential cookies and session storage for sign-in, security, carts, preferences, and core functionality. Blocking essential storage may prevent parts of the service from working. Account profile and message-privacy controls are available within the application.']],
                ],
                'sources' => [
                    ['label' => 'National Privacy Commission - Data Privacy Act of 2012', 'url' => 'https://privacy.gov.ph/data-privacy-act/'],
                    ['label' => 'National Privacy Commission - Data subject rights', 'url' => 'https://privacy.gov.ph/data-subject-rights/'],
                ],
            ],
            'seller' => [
                'title' => 'Seller Policy',
                'summary' => 'Standards for seller identity, listings, inventory, fulfilment, customer care, and records.',
                'icon' => 'bi-shop',
                'sections' => [
                    ['title' => '1. Seller eligibility and responsibility', 'paragraphs' => ['Sellers must provide truthful identity and shop information, maintain any registrations, permits, licences, and authorizations required for their activities, and keep their contact and fulfilment information current. Approval or a platform badge does not transfer the seller’s legal responsibilities to Ani-Mart.']],
                    ['title' => '2. Listing standards', 'bullets' => ['Use accurate product names, photos, category, origin, condition, harvest information, package size, unit, and description.', 'State the full selling price and do not use misleading reference prices or hidden charges.', 'Only list stock that is genuinely available and promptly update inventory.', 'Do not make false safety, organic, health, certification, or government-approval claims.', 'Comply with the Prohibited-Product Rules and applicable food, consumer, intellectual-property, and trade requirements.']],
                    ['title' => '3. Orders and fulfilment', 'bullets' => ['Review new orders promptly and only accept orders you can fulfil.', 'Do not mark an order as preparing, dispatched, delivered, or completed unless that status is accurate.', 'Package and handle products in a manner appropriate to their nature and food-safety needs.', 'Honor the recorded price, quantity, delivery fee, substitution preference, and agreed delivery arrangements.', 'Communicate material delays, shortages, substitutions, or safety concerns through the platform.']],
                    ['title' => '4. Cancellations, returns, and refunds', 'paragraphs' => ['Sellers must answer eligible cancellation and return requests honestly and provide a clear reason. When a cancellation is accepted before preparation, reserved quantities are returned to inventory. For approved returns, sellers must record quantities actually received, the approved and actually refunded amounts, refund method, reference when available, evidence, and final resolution. Cash refunds must be recorded even when the original payment was COD.']],
                    ['title' => '5. Customer information and conduct', 'bullets' => ['Use customer information only to fulfil, support, document, or lawfully resolve the transaction.', 'Do not move a transaction off-platform to avoid records or safety controls.', 'Do not retaliate against complaints, manipulate feedback, or pressure customers to provide false statements.', 'Keep communications professional and preserve relevant evidence.']],
                    ['title' => '6. Monitoring and enforcement', 'paragraphs' => ['Ani-Mart may review listings and seller activity, request corrections or evidence, limit visibility, hold a listing for review, suspend products or shops, adjust trust indicators, or close accounts for repeated or serious violations. Urgent safety risks may be acted on without advance notice.']],
                ],
                'sources' => [['label' => 'DTI eCommerce laws and policy issuances', 'url' => 'https://ecommerce.dti.gov.ph/related-laws-policy-issuance/']],
            ],
            'prohibited-products' => [
                'title' => 'Prohibited-Product Rules',
                'summary' => 'Products that may not be listed, promoted, requested, or sold through Ani-Mart.',
                'icon' => 'bi-slash-circle',
                'sections' => [
                    ['title' => '1. Always prohibited', 'bullets' => ['Illegal, stolen, fraudulently obtained, counterfeit, or intellectual-property-infringing goods.', 'Weapons, explosives, ammunition, illegal drugs, drug paraphernalia, tobacco, vapor products, and alcoholic products.', 'Poisonous, adulterated, contaminated, spoiled, expired, recalled, or otherwise unsafe food and agricultural products.', 'Protected wildlife, unlawfully sourced plants or animals, and live animals.', 'Human remains, bodily materials, obscene material, or products intended to facilitate violence, fraud, or unlawful surveillance.', 'Products subject to a government prohibition, seizure, recall, quarantine, or stop-sale direction.']],
                    ['title' => '2. Regulated products', 'paragraphs' => ['Medicines, veterinary drugs, pesticides, fertilizers, seeds, processed foods, supplements, cosmetics, and other regulated products may not be listed unless the seller and product hold every authorization required by law and Ani-Mart expressly supports that category. A seller must be able to provide current documentary proof on request. Ani-Mart may prohibit a regulated category even when off-platform sale might otherwise be lawful.']],
                    ['title' => '3. Misleading and unsafe listings', 'bullets' => ['Do not conceal damage, contamination, expiration, known allergens, or material quality defects.', 'Do not claim a product cures, treats, or prevents disease without lawful authorization and substantiation.', 'Do not misuse government seals, certification marks, organic claims, geographical indications, or another seller’s brand.', 'Do not evade these rules by misspelling names, using coded language, placing the offer in an image, or directing users off-platform.']],
                    ['title' => '4. Reporting and enforcement', 'paragraphs' => ['Users should report a suspected prohibited product through the available reporting tools. Ani-Mart may remove or restrict a listing, preserve records, suspend the seller, notify affected users, or refer the matter to the appropriate authority. Listing removal does not prevent additional account or legal action.']],
                ],
                'sources' => [
                    ['label' => 'Philippine FDA verification portal', 'url' => 'https://verification.fda.gov.ph/contact_us.php'],
                    ['label' => 'Philippine FDA administrative orders', 'url' => 'https://www.fda.gov.ph/category/administrative-order/'],
                ],
            ],
            'cancellation-returns' => [
                'title' => 'Cancellation and Return Policy',
                'summary' => 'Eligibility, decisions, evidence, inventory restoration, and refund records.',
                'icon' => 'bi-arrow-left-right',
                'sections' => [
                    ['title' => '1. Cancellation before preparation', 'paragraphs' => ['A customer may request cancellation while an order is pending or accepted. Once preparation starts, the standard cancellation request is no longer available because products may already have been handled or prepared. The request must include a meaningful reason.']],
                    ['title' => '2. Seller cancellation decision', 'paragraphs' => ['The seller reviews the request and accepts or declines it with a recorded reason. A pending request pauses further order-status advancement until the seller responds. If accepted, the order is cancelled and all reserved product and variant quantities are returned to inventory exactly once. Both parties receive a notification and retain the request and decision record.']],
                    ['title' => '3. Returns after delivery', 'paragraphs' => ['A delivered or completed order may be submitted for return through the order history. The customer selects each product and requested quantity, explains the reason, and attaches evidence. The requested refund is calculated from the recorded purchase price. Submission does not guarantee approval.']],
                    ['title' => '4. Seller return decision', 'paragraphs' => ['The seller approves or rejects the request with a reason. An approval records the approved quantities and maximum approved refund. A rejection remains in the history with the seller’s reason. Evidence and communications should be retained in Ani-Mart.']],
                    ['title' => '5. Final resolution and refunds', 'paragraphs' => ['For an approved return, the seller records the quantities actually received, amount actually refunded, refund method, reference when available, and evidence. Requested, approved, and actual refund amounts are kept separately. Refunds may be cash, bank transfer, e-wallet, another documented method, or zero when no refund was issued. COD orders still require an actual refund record when money is returned.']],
                    ['title' => '6. Exceptions and escalation', 'paragraphs' => ['Perishable condition, customer handling, food-safety concerns, mismatch with the request, missing evidence, or products that cannot lawfully be resold may affect a decision. Users may use the dispute and reporting tools when they believe a decision is unsafe, dishonest, or inconsistent with applicable law. Mandatory consumer rights remain unaffected.']],
                ],
                'sources' => [['label' => 'DTI eCommerce laws and policy issuances', 'url' => 'https://ecommerce.dti.gov.ph/related-laws-policy-issuance/']],
            ],
        ];
    }

    public static function find(string $slug): ?array
    {
        $policies = self::all();
        return isset($policies[$slug]) ? ['slug' => $slug] + $policies[$slug] : null;
    }
}
