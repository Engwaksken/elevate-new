import 'api_service.dart';

/// Everything the AI career mentor screen needs from the server. Injectable so
/// widget tests can run without the network.
abstract class AiMentorClient {
  /// `{enabled, messages: [{id, role, content, saved, actions}]}`, oldest first.
  Future<Map<String, dynamic>> history();
  Future<Map<String, dynamic>> send(String message, List<Map<String, String>> history);
  Future<Map<String, dynamic>> toggleSaved(int messageId);
  Future<void> clear();
  Future<Map<String, dynamic>> approve(int stepId, {required String title, required String dueOn});
  Future<Map<String, dynamic>> dismiss(int stepId);
  Future<Map<String, dynamic>> toggleDone(int stepId);
  Future<Map<String, dynamic>> plan({String? status, int page = 1});
  Future<Map<String, dynamic>> saved({int page = 1});
}

class ApiAiMentorClient implements AiMentorClient {
  const ApiAiMentorClient();

  ApiService get _api => ApiService.instance;

  @override
  Future<Map<String, dynamic>> history() => _api.mentorshipAssistantHistory();

  @override
  Future<Map<String, dynamic>> send(String message, List<Map<String, String>> history) =>
      _api.mentorshipAssistant(message: message, history: history);

  @override
  Future<Map<String, dynamic>> toggleSaved(int messageId) => _api.toggleMentorAnswerSaved(messageId);

  @override
  Future<void> clear() => _api.clearMentorHistory();

  @override
  Future<Map<String, dynamic>> approve(int stepId, {required String title, required String dueOn}) =>
      _api.approveMentorStep(stepId, title: title, dueOn: dueOn);

  @override
  Future<Map<String, dynamic>> dismiss(int stepId) => _api.dismissMentorStep(stepId);

  @override
  Future<Map<String, dynamic>> toggleDone(int stepId) => _api.toggleMentorStepDone(stepId);

  @override
  Future<Map<String, dynamic>> plan({String? status, int page = 1}) =>
      _api.mentorshipAssistantPlan(status: status, page: page);

  @override
  Future<Map<String, dynamic>> saved({int page = 1}) => _api.mentorshipAssistantSaved(page: page);
}
