import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

class _Step {
  const _Step(this.icon, this.title, this.body, [this.bullets = const []]);

  final IconData icon;
  final String title;
  final String body;
  final List<String> bullets;
}

const _steps = [
  _Step(
    Icons.school_outlined,
    'Welcome to Scholarly',
    'The official scholarship app of the Sangguniang Kabataan of Barangay Cabay, Tiaong. Register, follow your application, and claim your assistance with a QR code, all from your phone.',
  ),
  _Step(
    Icons.assignment_outlined,
    '1. Register once',
    'Open the Progress tab and start your scholar registration.',
    ['Fill in your personal and school details', 'Attach a 2x2 photo', 'Attach your enrollment form'],
  ),
  _Step(
    Icons.notifications_active_outlined,
    '2. Watch your progress',
    'The scholarship office reviews your registration. You will see a green banner and a badge on the Progress tab whenever there is news.',
    ['If more information is needed, update your registration right in the app', 'Updates marked NEW are the ones you have not read yet'],
  ),
  _Step(
    Icons.verified_outlined,
    '3. Get your scholar profile',
    'Once approved, your scholar profile is created. Program assignment and funding are confirmed separately by the SK office.',
  ),
  _Step(
    Icons.qr_code_2,
    '4. Claim with your QR',
    'On distribution day, open My QR and show it at the desk.',
    ['Staff scan your profile QR first', 'Then your voucher QR to record the claim', 'Your claim history is saved under History'],
  ),
];

/// Full-screen first-login guide. Completes with `true` when finished or skipped.
class WelcomeWizard extends StatefulWidget {
  const WelcomeWizard({super.key});

  static Future<void> show(BuildContext context) => Navigator.of(context).push<void>(
        MaterialPageRoute(fullscreenDialog: true, builder: (_) => const WelcomeWizard()),
      );

  @override
  State<WelcomeWizard> createState() => _WelcomeWizardState();
}

class _WelcomeWizardState extends State<WelcomeWizard> {
  final _controller = PageController();
  int _page = 0;

  bool get _isLast => _page == _steps.length - 1;

  void _next() {
    if (_isLast) {
      Navigator.of(context).pop();
    } else {
      _controller.nextPage(duration: const Duration(milliseconds: 260), curve: Curves.easeOut);
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.page,
      body: SafeArea(
        child: Column(
          children: [
            Align(
              alignment: Alignment.centerRight,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(8, 4, 12, 0),
                child: TextButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: Text(_isLast ? '' : 'SKIP', style: const TextStyle(color: AppTheme.textSecondary)),
                ),
              ),
            ),
            Expanded(
              child: PageView.builder(
                controller: _controller,
                itemCount: _steps.length,
                onPageChanged: (i) => setState(() => _page = i),
                itemBuilder: (_, i) => _stepView(_steps[i]),
              ),
            ),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(_steps.length, (i) {
                final active = i == _page;
                return AnimatedContainer(
                  duration: const Duration(milliseconds: 200),
                  margin: const EdgeInsets.symmetric(horizontal: 3),
                  width: active ? 22 : 7,
                  height: 7,
                  decoration: BoxDecoration(
                    color: active ? AppTheme.accent : AppTheme.elevated,
                    borderRadius: BorderRadius.circular(99),
                  ),
                );
              }),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 20, 24, 24),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _next,
                  child: Text(_isLast ? 'GET STARTED' : 'NEXT'),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _stepView(_Step step) {
    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(28, 12, 28, 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 24),
          Container(
            width: 76,
            height: 76,
            decoration: BoxDecoration(
              color: AppTheme.accent.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(22),
              border: Border.all(color: AppTheme.accent.withValues(alpha: 0.4)),
            ),
            child: Icon(step.icon, size: 38, color: AppTheme.accent),
          ),
          const SizedBox(height: 28),
          Text(step.title, style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w800, height: 1.15)),
          const SizedBox(height: 14),
          Text(step.body, style: const TextStyle(fontSize: 15, color: AppTheme.textSecondary, height: 1.5)),
          if (step.bullets.isNotEmpty) ...[
            const SizedBox(height: 20),
            ...step.bullets.map(
              (b) => Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Padding(
                      padding: EdgeInsets.only(top: 2),
                      child: Icon(Icons.check_circle, size: 18, color: AppTheme.accent),
                    ),
                    const SizedBox(width: 10),
                    Expanded(child: Text(b, style: const TextStyle(fontSize: 14, height: 1.4))),
                  ],
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}
