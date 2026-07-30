String normalizeQrPayload(String raw) {
  var payload = raw.trim().replaceAll(RegExp(r'[\x00-\x1F\x7F]'), '');
  final embedded = RegExp(r'(SCH\|[^\s]+)|(VCH\|[^\s]+)|(VCH[-A-Z0-9]+)', caseSensitive: false).firstMatch(payload);
  if (embedded != null) {
    return embedded.group(0)!;
  }
  return payload;
}
