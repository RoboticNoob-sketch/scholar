import 'package:flutter_test/flutter_test.dart';
import 'package:scholarly_mobile/main.dart';

void main() {
  testWidgets('App loads bootstrap screen', (WidgetTester tester) async {
    await tester.pumpWidget(const ScholarlyApp());
    await tester.pump();

    expect(find.byType(ScholarlyApp), findsOneWidget);
  });
}
