import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/session_service.dart';
import '../theme/app_theme.dart';
import '../widgets/app_card.dart';
import '../widgets/empty_state.dart';
import '../widgets/page_header.dart';
import '../utils/application_updates.dart';

class ApplicationScreen extends StatefulWidget {
  const ApplicationScreen({super.key, required this.isVisible, required this.onUnreadChanged});

  final bool isVisible;
  final ValueChanged<int> onUnreadChanged;

  @override
  State<ApplicationScreen> createState() => _ApplicationScreenState();
}

class _ApplicationScreenState extends State<ApplicationScreen> {
  static const _schoolOptions = ['SLSU Tiaong Campus', 'Other'];
  static const _courseOptions = ['BS Information Technology', 'BS Computer Science', 'STEM', 'ABM', 'HUMSS', 'GAS', 'TVL', 'Other'];

  final _formKey = GlobalKey<FormState>();
  final _firstName = TextEditingController();
  final _lastName = TextEditingController();
  final _phone = TextEditingController();
  final _school = TextEditingController();
  final _schoolOther = TextEditingController();
  final _course = TextEditingController();
  final _courseOther = TextEditingController();
  final _yearLevel = TextEditingController();
  final _note = TextEditingController();

  Map<String, dynamic>? _data;
  Map<String, dynamic>? _editing;
  PlatformFile? _photo;
  PlatformFile? _enrollmentForm;
  bool _loading = true;
  bool _newSession = true;
  int _highlightAfter = -1;
  bool _saving = false;
  bool _privacyAcknowledged = false;
  bool _showForm = false;
  String? _schoolChoice;
  String? _courseChoice;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void didUpdateWidget(covariant ApplicationScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!oldWidget.isVisible && widget.isVisible) {
      _newSession = true;
      _load();
    }
  }

  @override
  void dispose() {
    _firstName.dispose();
    _lastName.dispose();
    _phone.dispose();
    _school.dispose();
    _schoolOther.dispose();
    _course.dispose();
    _courseOther.dispose();
    _yearLevel.dispose();
    _note.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final response = await ApiService.getApplications();
      if (!mounted) return;
      if (response['success'] != true) {
        setState(() => _error = response['error']?.toString() ?? 'Could not load registration progress.');
        return;
      }
      final updates = (response['updates'] as List? ?? const [])
          .whereType<Map>()
          .map((event) => Map<String, dynamic>.from(event))
          .toList();
      setState(() => _data = {...response, 'updates': updates});
      final latestId = latestUpdateId(updates);
      final lastRead = await SessionService.getLastApplicationUpdateId();
      // Remember what was unread when the tab was opened so it stays highlighted until the user leaves.
      if (_newSession && widget.isVisible) {
        _newSession = false;
        final highlightAfter = lastRead ?? latestId;
        if (mounted) setState(() => _highlightAfter = highlightAfter);
      }
      if (lastRead == null) {
        await SessionService.saveLastApplicationUpdateId(widget.isVisible ? latestId : 0);
        widget.onUnreadChanged(widget.isVisible ? 0 : updates.length);
      } else {
        final unread = updates.where((event) => _intValue(event['id']) > lastRead).length;
        widget.onUnreadChanged(widget.isVisible ? 0 : unread);
        if (widget.isVisible && latestId > lastRead) {
          await SessionService.saveLastApplicationUpdateId(latestId);
        }
      }
    } catch (_) {
      if (mounted) setState(() => _error = 'Connection failed. Pull to refresh.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _startNewApplication() {
    _editing = null;
    _firstName.clear();
    _lastName.clear();
    _phone.clear();
    _school.clear();
    _schoolOther.clear();
    _course.clear();
    _courseOther.clear();
    _yearLevel.clear();
    _note.clear();
    _photo = null;
    _enrollmentForm = null;
    _privacyAcknowledged = false;
    _schoolChoice = null;
    _courseChoice = null;
    setState(() => _showForm = true);
  }

  void _editApplication(Map<String, dynamic> application) {
    _editing = application;
    _firstName.text = application['first_name']?.toString() ?? '';
    _lastName.text = application['last_name']?.toString() ?? '';
    _phone.text = application['phone']?.toString() ?? '';
    final school = application['school_name']?.toString() ?? '';
    _schoolChoice = _schoolOptions.contains(school) ? school : 'Other';
    _schoolOther.text = _schoolChoice == 'Other' ? school : '';
    final course = application['course']?.toString() ?? '';
    _courseChoice = _courseOptions.contains(course) ? course : 'Other';
    _courseOther.text = _courseChoice == 'Other' ? course : '';
    _yearLevel.text = application['year_level']?.toString() ?? '';
    _note.text = application['application_note']?.toString() ?? '';
    _photo = null;
    _enrollmentForm = null;
    _privacyAcknowledged = true;
    setState(() => _showForm = true);
  }

  Future<void> _pickDocument({required bool photo}) async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: photo ? const ['jpg', 'jpeg', 'png'] : const ['pdf', 'jpg', 'jpeg', 'png'],
      withData: true,
    );
    if (result == null || result.files.isEmpty || !mounted) return;
    final file = result.files.single;
    final maxBytes = photo ? 2 * 1024 * 1024 : 5 * 1024 * 1024;
    if (file.size > maxBytes) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(photo ? 'Photo must be 2 MB or smaller.' : 'Enrollment form must be 5 MB or smaller.')),
      );
      return;
    }
    setState(() {
      if (photo) {
        _photo = file;
      } else {
        _enrollmentForm = file;
      }
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (!_privacyAcknowledged) {
      setState(() => _error = 'Please acknowledge the privacy notice before submitting.');
      return;
    }
    if (_photo == null && !(_editing?['has_photo'] == true)) {
      setState(() => _error = 'Select your square 2x2 photo.');
      return;
    }
    if (_enrollmentForm == null && !(_editing?['has_enrollment_form'] == true)) {
      setState(() => _error = 'Select your enrollment form.');
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });
    final fields = <String, String>{
      'first_name': _firstName.text.trim(),
      'last_name': _lastName.text.trim(),
      'phone': _phone.text.trim(),
      'school_name': _schoolChoice == 'Other' ? _schoolOther.text.trim() : (_schoolChoice ?? ''),
      'course': _courseChoice == 'Other' ? _courseOther.text.trim() : (_courseChoice ?? ''),
      'year_level': _yearLevel.text.trim(),
      'application_note': _note.text.trim(),
      'privacy_acknowledged': '1',
      if (_editing != null) 'application_id': _editing!['id'].toString(),
    };
    try {
      final response = await ApiService.submitApplication(
        fields: fields,
        photo: _photo,
        enrollmentForm: _enrollmentForm,
      );
      if (!mounted) return;
      if (response['success'] == true) {
        setState(() => _showForm = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(response['message']?.toString() ?? 'Registration submitted.')),
        );
        await _load();
      } else {
        setState(() => _error = response['error']?.toString() ?? 'Could not submit registration.');
      }
    } catch (_) {
      if (mounted) setState(() => _error = 'Upload failed. Check your connection and try again.');
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final applications = (_data?['applications'] as List? ?? const []).whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
    final updates = (_data?['updates'] as List? ?? const []).whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();

    return RefreshIndicator(
      onRefresh: _load,
      color: AppTheme.accent,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
        children: [
          PageHeader(
            title: 'Application progress',
            subtitle: 'Registration status and scholarship office updates',
            trailing: IconButton(
              tooltip: 'Refresh progress',
              onPressed: _loading ? null : _load,
              icon: const Icon(Icons.refresh),
            ),
          ),
          if (_loading)
            const Center(child: Padding(padding: EdgeInsets.all(40), child: CircularProgressIndicator(strokeWidth: 2)))
          else if (_error != null && _data == null)
            EmptyState(message: _error!, icon: Icons.cloud_off_outlined)
          else ...[
            if (_error != null) _messageBanner(_error!, AppTheme.negative, Icons.error_outline),
            if (_newUpdateCount(updates) > 0) _newUpdatesBanner(updates),
            if (applications.isEmpty)
              _emptyApplicationState()
            else ...applications.map(_applicationCard),
            const SizedBox(height: 18),
            _updatesSection(updates),
            if (_data?['can_apply'] == true && !_showForm) ...[
              const SizedBox(height: 18),
              ElevatedButton.icon(
                onPressed: _startNewApplication,
                icon: const Icon(Icons.note_add_outlined),
                label: const Text('START SCHOLAR REGISTRATION'),
              ),
            ],
            if (_showForm) ...[
              const SizedBox(height: 18),
              _applicationForm(),
            ],
          ],
        ],
      ),
    );
  }

  Widget _emptyApplicationState() {
    if (_data?['has_scholar'] == true) {
      return const EmptyState(
        message: 'Your scholar profile is active. Application registration is for prospective scholars.',
        icon: Icons.school_outlined,
      );
    }
    return const EmptyState(
      message: 'No registration on file yet. Start a one-time scholar registration when you are ready.',
      icon: Icons.assignment_outlined,
    );
  }

  Widget _applicationCard(Map<String, dynamic> application) {
    final status = application['status']?.toString() ?? 'submitted';
    final color = _statusColor(status);
    final needsInfo = status == 'needs_info';
    final program = application['program_name']?.toString() ?? 'Scholar registration';
    final submitted = _formatDate(application['created_at']);
    final isGeneral = application['program_id'] == null;
    final hasScholar = application['scholar_id'] != null;

    return AppCard(
      accentTop: color,
      margin: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(isGeneral ? 'GENERAL REGISTRATION' : '${application['semester'] ?? ''} | ${application['academic_year'] ?? ''}',
                        style: const TextStyle(fontSize: 10, color: AppTheme.textTertiary, fontWeight: FontWeight.w700)),
                    const SizedBox(height: 5),
                    Text(program, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                  ],
                ),
              ),
              _statusPill(status, color),
            ],
          ),
          const SizedBox(height: 14),
          _progressTrack(status, color),
          const SizedBox(height: 12),
          Text('Submitted $submitted', style: const TextStyle(fontSize: 11, color: AppTheme.textTertiary)),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _documentChip('2x2 photo', application['has_photo'] == true),
              _documentChip('Enrollment form', application['has_enrollment_form'] == true),
            ],
          ),
          if (needsInfo && (application['review_note']?.toString().isNotEmpty ?? false)) ...[
            const SizedBox(height: 14),
            _messageBanner(application['review_note'].toString(), AppTheme.warning, Icons.mark_chat_unread_outlined,
                heading: 'Action requested'),
          ],
          if (status == 'approved') ...[
            const SizedBox(height: 12),
            Text(
              hasScholar
                  ? 'Your scholar profile is ready. Program assignment and funding approval are handled separately.'
                  : 'You are approved as a potential scholar. The scholarship office will prepare your profile next.',
              style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary, height: 1.45),
            ),
          ],
          if (status == 'declined' && (application['review_note']?.toString().isNotEmpty ?? false)) ...[
            const SizedBox(height: 12),
            _messageBanner(application['review_note'].toString(), AppTheme.textSecondary, Icons.info_outline,
                heading: 'Scholarship office note'),
          ],
          if (needsInfo) ...[
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: () => _editApplication(application),
              icon: const Icon(Icons.edit_note_outlined),
              label: const Text('UPDATE REGISTRATION'),
            ),
          ],
        ],
      ),
    );
  }

  Widget _progressTrack(String status, Color color) {
    final current = switch (status) {
      'submitted' => 0,
      'under_review' || 'needs_info' => 1,
      _ => 2,
    };
    final isDeclined = status == 'declined';
    const labels = ['Submitted', 'Review', 'Decision'];
    return Row(
      children: List.generate(labels.length, (index) {
        final reached = index <= current;
        final stepColor = isDeclined && index == 2 ? AppTheme.negative : (reached ? color : AppTheme.elevated);
        return Expanded(
          child: Row(
            children: [
              Column(
                children: [
                  Container(
                    width: 10,
                    height: 10,
                    decoration: BoxDecoration(color: stepColor, shape: BoxShape.circle),
                  ),
                  const SizedBox(height: 5),
                  Text(labels[index], style: TextStyle(fontSize: 9, color: reached ? AppTheme.textSecondary : AppTheme.textTertiary)),
                ],
              ),
              if (index < labels.length - 1)
                Expanded(child: Container(height: 2, margin: const EdgeInsets.only(left: 6, right: 6, bottom: 16), color: index < current ? color : AppTheme.elevated)),
            ],
          ),
        );
      }),
    );
  }

  Widget _updatesSection(List<Map<String, dynamic>> updates) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const PageHeader(title: 'Updates', subtitle: 'Messages from the scholarship office'),
        if (updates.isEmpty)
          const AppCard(
            child: Row(
              children: [
                Icon(Icons.notifications_none, color: AppTheme.textTertiary),
                SizedBox(width: 12),
                Expanded(child: Text('No updates yet. New review activity will appear here.', style: TextStyle(color: AppTheme.textSecondary, fontSize: 12))),
              ],
            ),
          )
        else
          ...updates.map(_updateCard),
      ],
    );
  }

  Widget _updateCard(Map<String, dynamic> update) {
    final action = update['action']?.toString() ?? '';
    final status = update['status']?.toString();
    final reviewed = action == 'application_reviewed';
    final converted = action == 'application_converted_to_scholar';
    final text = describeApplicationUpdate(update);
    final isNew = _isNewUpdate(update);
    final color = converted ? AppTheme.accent : (status == 'declined' ? AppTheme.negative : AppTheme.textSecondary);

    return Container(
      margin: const EdgeInsets.only(bottom: 9),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        boxShadow: isNew ? [BoxShadow(color: AppTheme.accent.withValues(alpha: 0.18), blurRadius: 14)] : null,
        border: isNew ? Border.all(color: AppTheme.accent.withValues(alpha: 0.7)) : null,
      ),
      child: AppCard(
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(converted ? Icons.school_outlined : (reviewed ? Icons.mark_email_read_outlined : Icons.receipt_long_outlined),
                color: isNew ? AppTheme.accent : color, size: 20),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(child: Text(text.title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13))),
                      if (isNew)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                          decoration: BoxDecoration(color: AppTheme.accent, borderRadius: BorderRadius.circular(99)),
                          child: const Text('NEW', style: TextStyle(fontSize: 9, fontWeight: FontWeight.w800, color: Colors.black)),
                        ),
                    ],
                  ),
                  const SizedBox(height: 3),
                  Text(text.subtitle, style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary, height: 1.4)),
                  const SizedBox(height: 5),
                  Text(_formatDate(update['created_at']), style: const TextStyle(fontSize: 10, color: AppTheme.textTertiary)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
  Widget _applicationForm() {
    final isEditing = _editing != null;
    return AppCard(
      accentTop: AppTheme.accent,
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            PageHeader(title: isEditing ? 'Respond to request' : 'Scholar registration', subtitle: 'Required details and documents'),
            Row(
              children: [
                Expanded(child: _input(_firstName, 'First name')),
                const SizedBox(width: 10),
                Expanded(child: _input(_lastName, 'Last name')),
              ],
            ),
            const SizedBox(height: 12),
            _input(_phone, 'Contact number', keyboardType: TextInputType.phone),
            const SizedBox(height: 12),
            _choiceDropdown(
              label: 'School',
              value: _schoolChoice,
              options: _schoolOptions,
              onChanged: (value) => setState(() => _schoolChoice = value),
            ),
            if (_schoolChoice == 'Other') ...[
              const SizedBox(height: 10),
              _input(_schoolOther, 'Enter school name'),
            ],
            const SizedBox(height: 12),
            _choiceDropdown(
              label: 'Course or strand',
              value: _courseChoice,
              options: _courseOptions,
              onChanged: (value) => setState(() => _courseChoice = value),
            ),
            if (_courseChoice == 'Other') ...[
              const SizedBox(height: 10),
              _input(_courseOther, 'Enter course or strand', maxLength: 120),
            ],
            const SizedBox(height: 12),
            _input(_yearLevel, 'Year level'),
            const SizedBox(height: 12),
            _input(_note, 'Note to the scholarship office (optional)', required: false, maxLines: 3, maxLength: 2000),
            const SizedBox(height: 16),
            _filePicker(
              title: '2x2 ID photo',
              helper: 'Square JPG or PNG, minimum 300 x 300 px, up to 2 MB.',
              file: _photo,
              alreadyUploaded: _editing?['has_photo'] == true,
              onPressed: () => _pickDocument(photo: true),
            ),
            const SizedBox(height: 12),
            _filePicker(
              title: 'Enrollment form',
              helper: 'PDF, JPG, or PNG, up to 5 MB.',
              file: _enrollmentForm,
              alreadyUploaded: _editing?['has_enrollment_form'] == true,
              onPressed: () => _pickDocument(photo: false),
            ),
            const SizedBox(height: 12),
            const Text(
              'These private documents are available only to authorized scholarship office administrators for application review.',
              style: TextStyle(fontSize: 11, color: AppTheme.textSecondary, height: 1.45),
            ),
            const SizedBox(height: 10),
            CheckboxListTile(
              value: _privacyAcknowledged,
              onChanged: _saving ? null : (value) => setState(() => _privacyAcknowledged = value ?? false),
              contentPadding: EdgeInsets.zero,
              controlAffinity: ListTileControlAffinity.leading,
              title: const Text('I confirm these details are accurate and agree that the scholarship office may use my application details and documents to review and contact me.',
                  style: TextStyle(fontSize: 11, color: AppTheme.textSecondary, height: 1.4)),
            ),
            if (_error != null) ...[
              const SizedBox(height: 8),
              _messageBanner(_error!, AppTheme.negative, Icons.error_outline),
            ],
            const SizedBox(height: 12),
            ElevatedButton.icon(
              onPressed: _saving ? null : _submit,
              icon: Icon(_saving ? Icons.hourglass_top : Icons.send_outlined),
              label: Text(_saving ? 'SENDING...' : isEditing ? 'RESUBMIT REGISTRATION' : 'SUBMIT REGISTRATION'),
            ),
            TextButton(
              onPressed: _saving ? null : () => setState(() => _showForm = false),
              child: const Text('CANCEL'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _input(
    TextEditingController controller,
    String label, {
    TextInputType? keyboardType,
    bool required = true,
    int maxLines = 1,
    int? maxLength,
  }) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      maxLines: maxLines,
      maxLength: maxLength,
      textCapitalization: TextCapitalization.sentences,
      validator: required ? (value) => value == null || value.trim().isEmpty ? 'Required' : null : null,
      decoration: InputDecoration(labelText: label),
    );
  }

  Widget _choiceDropdown({
    required String label,
    required String? value,
    required List<String> options,
    required ValueChanged<String?> onChanged,
  }) {
    return DropdownButtonFormField<String>(
      key: ValueKey('$label-$value'),
      initialValue: value,
      decoration: InputDecoration(labelText: label),
      items: options.map((option) => DropdownMenuItem(value: option, child: Text(option))).toList(),
      onChanged: onChanged,
      validator: (selected) => selected == null || selected.isEmpty ? 'Choose an option' : null,
    );
  }

  Widget _filePicker({
    required String title,
    required String helper,
    required PlatformFile? file,
    required bool alreadyUploaded,
    required VoidCallback onPressed,
  }) {
    final name = file?.name ?? (alreadyUploaded ? 'Already uploaded' : 'No file selected');
    final valid = file != null || alreadyUploaded;
    return OutlinedButton.icon(
      onPressed: _saving ? null : onPressed,
      icon: Icon(valid ? Icons.check_circle_outline : Icons.upload_file_outlined, color: valid ? AppTheme.accent : AppTheme.textSecondary),
      label: Padding(
        padding: const EdgeInsets.symmetric(vertical: 5),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
            const SizedBox(height: 2),
            Text(name, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 10, color: AppTheme.textSecondary)),
            Text(helper, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 9, color: AppTheme.textTertiary)),
          ],
        ),
      ),
      style: OutlinedButton.styleFrom(alignment: Alignment.centerLeft, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
    );
  }

  Widget _documentChip(String label, bool uploaded) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
      decoration: BoxDecoration(
        color: (uploaded ? AppTheme.accent : AppTheme.warning).withValues(alpha: 0.1),
        border: Border.all(color: (uploaded ? AppTheme.accent : AppTheme.warning).withValues(alpha: 0.25)),
        borderRadius: BorderRadius.circular(7),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(uploaded ? Icons.check_circle_outline : Icons.error_outline, size: 13, color: uploaded ? AppTheme.accent : AppTheme.warning),
          const SizedBox(width: 5),
          Text('$label ${uploaded ? 'uploaded' : 'missing'}', style: TextStyle(fontSize: 10, color: uploaded ? AppTheme.accent : AppTheme.warning)),
        ],
      ),
    );
  }

  Widget _statusPill(String status, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(7)),
      child: Text(_statusTitle(status), style: TextStyle(color: color, fontSize: 10, fontWeight: FontWeight.w700)),
    );
  }

  Widget _messageBanner(String message, Color color, IconData icon, {String? heading}) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.09),
        border: Border.all(color: color.withValues(alpha: 0.28)),
        borderRadius: BorderRadius.circular(9),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 17, color: color),
          const SizedBox(width: 9),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (heading != null) Text(heading, style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.w700)),
                if (heading != null) const SizedBox(height: 3),
                Text(message, style: TextStyle(color: color, fontSize: 11, height: 1.4)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _statusTitle(String status) => applicationStatusTitle(status);

  Color _statusColor(String status) => switch (status) {
        'needs_info' => AppTheme.warning,
        'approved' => AppTheme.accent,
        'declined' => AppTheme.negative,
        'under_review' => const Color(0xFF64B5F6),
        _ => AppTheme.textSecondary,
      };

  String _formatDate(Object? value) {
    final parsed = DateTime.tryParse(value?.toString() ?? '');
    if (parsed == null) return 'Recently';
    final date = parsed.toLocal();
    return '${date.month}/${date.day}/${date.year} Â· ${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')}';
  }

  int _intValue(Object? value) => updateId(value);

  bool _isNewUpdate(Map<String, dynamic> update) => _highlightAfter >= 0 && _intValue(update['id']) > _highlightAfter;

  int _newUpdateCount(List<Map<String, dynamic>> updates) => updates.where(_isNewUpdate).length;

  Widget _newUpdatesBanner(List<Map<String, dynamic>> updates) {
    final count = _newUpdateCount(updates);
    final newest = updates.where(_isNewUpdate).reduce((a, b) => _intValue(a['id']) >= _intValue(b['id']) ? a : b);
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: _messageBanner(
        '${describeApplicationUpdate(newest).title}. Scroll down to Updates for details.',
        AppTheme.accent,
        Icons.notifications_active_outlined,
        heading: count == 1 ? '1 new update from the scholarship office' : '$count new updates from the scholarship office',
      ),
    );
  }
}
