class ApplicationUpdateText {
  const ApplicationUpdateText(this.title, this.subtitle);

  final String title;
  final String subtitle;
}

int updateId(Object? value) => value is int ? value : int.tryParse(value?.toString() ?? '') ?? 0;

String applicationStatusTitle(String status) => switch (status) {
      'submitted' => 'Submitted',
      'under_review' => 'Under review',
      'needs_info' => 'More information needed',
      'approved' => 'Approved',
      'declined' => 'Not selected',
      _ => status,
    };

ApplicationUpdateText describeApplicationUpdate(Map<String, dynamic> update) {
  final action = update['action']?.toString() ?? '';
  final status = update['status']?.toString();

  if (action == 'application_converted_to_scholar') {
    return const ApplicationUpdateText(
      'Scholar profile created',
      'Program assignment and funding approval are handled separately.',
    );
  }
  if (action == 'application_reviewed') {
    return ApplicationUpdateText(
      applicationStatusTitle(status ?? 'under_review'),
      status == 'needs_info'
          ? 'Open your registration above and review the scholarship office note.'
          : 'Your registration status was updated by the scholarship office.',
    );
  }
  if (action == 'application_resubmitted') {
    return const ApplicationUpdateText(
      'Updated registration submitted',
      'Your requested changes have been sent for review.',
    );
  }
  return const ApplicationUpdateText(
    'Registration received',
    'Your registration is in the scholarship office queue.',
  );
}

/// Highest update id in the list, or 0 when empty.
int latestUpdateId(List<Map<String, dynamic>> updates) =>
    updates.fold<int>(0, (maxId, event) => updateId(event['id']) > maxId ? updateId(event['id']) : maxId);
