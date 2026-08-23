class Payment {
  const Payment({
    required this.id,
    required this.reference,
    required this.provider,
    required this.amount,
    required this.currency,
    required this.status,
    required this.payableType,
    this.checkoutUrl,
    this.paidAt,
    required this.createdAt,
  });

  final int id;
  final String reference;
  final String provider;
  final int amount;
  final String currency;
  final String status;
  final String payableType;
  final String? checkoutUrl;
  final DateTime? paidAt;
  final DateTime createdAt;

  bool get isPaid => status == 'paid';
  bool get isPending => status == 'pending';

  factory Payment.fromJson(Map<String, dynamic> json) => Payment(
    id: json['id'] as int,
    reference: json['reference'] as String,
    provider: json['provider'] as String,
    amount: json['amount'] as int,
    currency: json['currency'] as String,
    status: json['status'] as String,
    payableType: json['payable_type'] as String,
    checkoutUrl: json['checkout_url'] as String?,
    paidAt: json['paid_at'] != null
        ? DateTime.tryParse(json['paid_at'] as String)
        : null,
    createdAt: DateTime.parse(json['created_at'] as String),
  );
}
