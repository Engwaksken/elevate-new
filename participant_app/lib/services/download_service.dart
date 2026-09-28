import 'dart:io';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api_service.dart';
import 'local_database.dart';

class DownloadService {
  DownloadService._();

  static final DownloadService instance = DownloadService._();

  final LocalDatabase _db = LocalDatabase.instance;

  Future<bool> wifiOnly() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool('wifi_only_downloads') ?? false;
  }

  Future<void> setWifiOnly(bool value) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('wifi_only_downloads', value);
  }

  Future<void> _checkConnectionPolicy() async {
    final results = await Connectivity().checkConnectivity();

    if (results.every((item) => item == ConnectivityResult.none)) {
      throw Exception('No internet connection.');
    }

    if (await wifiOnly() &&
        !results.contains(ConnectivityResult.wifi) &&
        !results.contains(ConnectivityResult.ethernet)) {
      throw Exception('Wi-Fi only downloads are enabled.');
    }
  }

  Future<String> download({
    required String key,
    required String url,
    required String suggestedName,
  }) async {
    await _checkConnectionPolicy();

    final directory = await getApplicationDocumentsDirectory();
    final downloadsDir = Directory(p.join(directory.path, 'offline_downloads'));
    if (!await downloadsDir.exists()) {
      await downloadsDir.create(recursive: true);
    }

    final safeName = suggestedName.replaceAll(RegExp(r'[^A-Za-z0-9._-]'), '_');
    final localPath = p.join(downloadsDir.path, safeName);

    await ApiService.instance.dio.download(url, localPath);

    await _db.saveDownload(
      downloadKey: key,
      localPath: localPath,
      sourceUrl: url,
      fileName: safeName,
    );

    return localPath;
  }

  Future<String?> localPath(String key) => _db.downloadPath(key);

  Future<void> open(String key) async {
    final path = await localPath(key);
    if (path == null || !File(path).existsSync()) {
      throw Exception('Offline file is not available.');
    }

    await OpenFilex.open(path);
  }

  Future<void> remove(String key) async {
    final path = await localPath(key);
    if (path != null) {
      final file = File(path);
      if (await file.exists()) {
        await file.delete();
      }
    }
    await _db.removeDownload(key);
  }
}
