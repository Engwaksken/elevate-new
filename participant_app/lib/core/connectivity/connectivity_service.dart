import 'package:connectivity_plus/connectivity_plus.dart';

class ConnectivityService {
  ConnectivityService({Connectivity? connectivity})
      : _c = connectivity ?? Connectivity();
  final Connectivity _c;
  Stream<bool> get onlineStream => _c.onConnectivityChanged
      .map((r) => r.any((x) => x != ConnectivityResult.none));
  Future<bool> get isOnline async =>
      (await _c.checkConnectivity()).any((x) => x != ConnectivityResult.none);
}
