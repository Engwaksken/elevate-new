/// Pure state of unsent reading time for one lesson. [LocalDatabase]
/// applies these transitions inside SQLite transactions; keeping them here
/// makes the "never lost, never double sent" rules unit-testable.
///
/// * [pending]: counted locally, not queued yet.
/// * [inFlight]: queued as operation [opId]; waiting for the server.
/// * [acknowledged]: the last lesson total the server reported.
class ReadingTimeEntry {
  const ReadingTimeEntry({
    this.pending = 0,
    this.inFlight = 0,
    this.opId,
    this.acknowledged = 0,
  });

  factory ReadingTimeEntry.fromRow(Map<String, dynamic>? row) {
    if (row == null) return const ReadingTimeEntry();
    return ReadingTimeEntry(
      pending: (row['pending_seconds'] as int?) ?? 0,
      inFlight: (row['in_flight_seconds'] as int?) ?? 0,
      opId: row['in_flight_op_id'] as String?,
      acknowledged: (row['server_seconds'] as int?) ?? 0,
    );
  }

  final int pending;
  final int inFlight;
  final String? opId;
  final int acknowledged;

  bool get hasBatchInFlight => opId != null;

  /// Seconds not yet confirmed by the server.
  int get unsent => pending + inFlight;

  Map<String, Object?> toRow() => {
        'pending_seconds': pending,
        'in_flight_seconds': inFlight,
        'in_flight_op_id': opId,
        'server_seconds': acknowledged,
      };

  ReadingTimeEntry add(int seconds) => seconds <= 0
      ? this
      : ReadingTimeEntry(
          pending: pending + seconds,
          inFlight: inFlight,
          opId: opId,
          acknowledged: acknowledged,
        );

  /// Starts a batch of up to [maxSeconds] as operation [newOpId]. Returns
  /// null when there's nothing to send or a batch is already in flight
  /// (that batch must be retried with its own id, never re-queued).
  ReadingTimeEntry? beginBatch(String newOpId, {int maxSeconds = 3600}) {
    if (hasBatchInFlight || pending <= 0) return null;
    final seconds = pending > maxSeconds ? maxSeconds : pending;
    return ReadingTimeEntry(
      pending: pending - seconds,
      inFlight: seconds,
      opId: newOpId,
      acknowledged: acknowledged,
    );
  }

  /// The server accepted [ackOpId] (processed or duplicate). [serverTotal]
  /// is the lesson total it reported, if any. Unknown ids are ignored.
  ReadingTimeEntry acknowledge(String ackOpId, {int? serverTotal}) {
    if (ackOpId != opId) return this;
    final expected = acknowledged + inFlight;
    final total = serverTotal ?? expected;
    return ReadingTimeEntry(
      pending: pending,
      acknowledged: total > acknowledged ? total : acknowledged,
    );
  }

  /// The server permanently rejected [rejectedOpId] (e.g. the lesson is no
  /// longer accessible): drop that batch so it isn't retried forever.
  ReadingTimeEntry reject(String rejectedOpId) {
    if (rejectedOpId != opId) return this;
    return ReadingTimeEntry(pending: pending, acknowledged: acknowledged);
  }

  /// Records a total seen in a server payload (never lowers it).
  ReadingTimeEntry withServerTotal(int seconds) => seconds <= acknowledged
      ? this
      : ReadingTimeEntry(
          pending: pending,
          inFlight: inFlight,
          opId: opId,
          acknowledged: seconds,
        );

  /// Best known total: the larger server total plus unsent seconds.
  int total({int serverSeconds = 0}) =>
      (serverSeconds > acknowledged ? serverSeconds : acknowledged) + unsent;
}
