import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:sunumarket/widgets/common.dart';

void main() {
  testWidgets('RatingStars affiche le bon nombre d\'étoiles pleines', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(
      const MaterialApp(home: Scaffold(body: RatingStars(rating: 3))),
    );

    expect(find.byIcon(Icons.star), findsNWidgets(3));
    expect(find.byIcon(Icons.star_border), findsNWidgets(2));
  });

  testWidgets('ProBadge affiche le texte PRO', (WidgetTester tester) async {
    await tester.pumpWidget(
      const MaterialApp(home: Scaffold(body: ProBadge())),
    );

    expect(find.text('PRO'), findsOneWidget);
  });
}
