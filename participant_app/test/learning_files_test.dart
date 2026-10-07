import 'package:elevateher360_participant/core/assignment_info.dart';
import 'package:elevateher360_participant/core/lesson_info.dart';
import 'package:elevateher360_participant/core/network/app_exception.dart';
import 'package:elevateher360_participant/core/theme/app_theme.dart';
import 'package:elevateher360_participant/screens/assignments_screen.dart';
import 'package:elevateher360_participant/services/download_service.dart';
import 'package:elevateher360_participant/widgets/learning_file_tile.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

Widget _app(Widget child) => MaterialApp(
      theme: AppTheme.light(),
      home: Scaffold(body: ListView(children: [child])),
    );

void main() {
  group('download policy', () {
    test('automatic offline cache keys are separated from user downloads', () {
      final key = DownloadService.lessonCacheKey(7, 12);
      expect(DownloadService.isAutomaticCacheKey(key), isTrue);
      expect(DownloadService.isAutomaticCacheKey('lesson_7_file_12'), isFalse);
      expect(DownloadService.assignmentCacheKey(8, 3),
          'offline_cache_assignment_8_file_3');
    });

    test('only spreadsheets, CSV and ZIP are downloadable by extension', () {
      for (final name in ['a.xlsx', 'B.XLS', 'c.csv', 'd.zip']) {
        expect(isDownloadableFileName(name), isTrue, reason: name);
      }
      for (final name in [
        'a.pdf',
        'b.docx',
        'c.pptx',
        'd.png',
        'e.mp4',
        'f.txt',
        'noext',
        null
      ]) {
        expect(isDownloadableFileName(name), isFalse, reason: '$name');
      }
    });

    test('server flags win over the extension', () {
      expect(
          LearningFileInfo({'name': 'a.pdf', 'downloadable': true})
              .downloadable,
          isTrue);
      expect(
          LearningFileInfo({'name': 'a.xlsx', 'downloadable': false}).viewOnly,
          isTrue);
      expect(LearningFileInfo({'name': 'a.xlsx', 'view_only': 1}).viewOnly,
          isTrue);
      expect(LearningFileInfo({'name': 'a.pdf'}).viewOnly, isTrue);
      expect(LearningFileInfo({'name': 'a.csv'}).downloadable, isTrue);
    });

    test('her own submissions are always downloadable', () {
      final file = LearningFileInfo(
        {'name': 'essay.pdf', 'downloadable': false},
        ownSubmission: true,
      );
      expect(file.downloadable, isTrue);
    });

    test('view kinds', () {
      expect(fileViewKindFor('a.PDF'), FileViewKind.pdf);
      expect(fileViewKindFor('a.jpeg'), FileViewKind.image);
      expect(fileViewKindFor('clip.mp4'), FileViewKind.video);
      expect(fileViewKindFor('talk.m4a'), FileViewKind.audio);
      expect(fileViewKindFor('notes.txt'), FileViewKind.text);
      expect(fileViewKindFor('slides.pptx'), FileViewKind.other);
      final wordFile = LearningFileInfo({
        'name': 'lesson.docx',
        'download_path': '/lessons/5/files/9/download',
      });
      expect(wordFile.viewKind, FileViewKind.pdf);
      expect(wordFile.readerPath, '/lessons/5/files/9/download?format=pdf');
      expect(fileViewKindFor('file', 'application/pdf'), FileViewKind.pdf);
    });

    test('legacy /storage/ URLs are never used', () {
      expect(
          LearningFileInfo({'download_url': 'https://x/storage/a.pdf'})
              .downloadPath,
          isNull);
    });
  });

  group('LessonInfo files', () {
    test('parses every files[] item with its flags', () {
      final lesson = LessonInfo({
        'id': 7,
        'title': 'Budgets',
        'has_file': true,
        'download_path': '/lessons/7/download',
        'file_name': 'guide.pdf',
        'file_downloadable': false,
        'files': [
          {
            'id': 1,
            'name': 'guide.pdf',
            'size_bytes': 2048,
            'downloadable': false,
            'view_only': true,
            'download_path': '/lessons/7/files/1/download',
          },
          {
            'id': 2,
            'name': 'budget.xlsx',
            'downloadable': true,
            'view_only': false,
            'download_path': '/lessons/7/files/2/download',
          },
        ],
      });

      final files = lesson.files;
      expect(files, hasLength(2));
      expect(files[0].viewOnly, isTrue);
      expect(files[1].downloadable, isTrue);
      expect(lesson.fileDownloadable, isFalse);
      // The primary file keeps the lesson key used by the course outline.
      expect(lesson.fileDownloadKey(files[0], 0), 'lesson_7');
      expect(lesson.fileDownloadKey(files[1], 1), 'lesson_7_file_2');
    });

    test('falls back to the primary file without files[]', () {
      final lesson = LessonInfo({
        'id': 3,
        'title': 'Sheet',
        'has_file': true,
        'download_path': '/lessons/3/download',
        'file_name': 'data.csv',
      });
      expect(lesson.files.single.name, 'data.csv');
      expect(lesson.files.single.downloadable, isTrue);
      expect(lesson.fileDownloadable, isTrue);
    });
  });

  group('AssignmentInfo files', () {
    test('parses attachments[] and the latest submission files', () {
      final a = AssignmentInfo({
        'id': 9,
        'title': 'Plan',
        'attachments': [
          {
            'id': 4,
            'name': 'brief.pdf',
            'downloadable': false,
            'download_path': '/assignments/9/attachments/4'
          },
          {
            'id': 5,
            'name': 'template.xlsx',
            'downloadable': true,
            'download_path': '/assignments/9/attachments/5'
          },
        ],
        'latest_submission': {
          'id': 1,
          'files': [
            {
              'id': 11,
              'name': 'mine.pdf',
              'downloadable': true,
              'download_path': '/submissions/files/11'
            },
          ],
        },
      });

      expect(a.attachments.map((f) => f.name), ['brief.pdf', 'template.xlsx']);
      expect(a.attachments.first.viewOnly, isTrue);
      expect(
          a.attachmentDownloadKey(a.attachments.last), 'assessment_9_file_5');
      expect(a.submittedFiles.single.downloadable, isTrue);
    });

    test('older servers: the single attachment_url', () {
      final a = AssignmentInfo({
        'id': 9,
        'title': 'Plan',
        'attachment_url':
            'https://site.example/api/v1/participant/assignments/9/attachment',
        'attachment_name': 'brief.docx',
      });
      final file = a.attachments.single;
      expect(file.name, 'brief.docx');
      expect(file.viewOnly, isTrue);
      expect(a.attachmentDownloadKey(file), 'assessment_9');
    });

    test('no attachments', () {
      expect(AssignmentInfo({'id': 1, 'attachments': []}).attachments, isEmpty);
      expect(AssignmentInfo({'id': 1}).attachments, isEmpty);
      expect(AssignmentInfo({'id': 1}).submittedFiles, isEmpty);
    });
  });

  group('SubmissionFileErrors', () {
    test('maps submission_files.N to each picked file', () {
      const error = AppException(
        AppErrorKind.validation,
        'The submission_files.1 must be a file of type: pdf.',
        fieldErrors: {
          'submission_files.1': 'The file must be a PDF, Word or image.'
        },
      );
      final errors = SubmissionFileErrors.of(error, fileCount: 3);
      expect(errors.perFile,
          [null, 'The file must be a PDF, Word or image.', null]);
      expect(errors.general, isNull);
    });

    test('a single file uses the legacy submission_file error', () {
      const error = AppException(
        AppErrorKind.validation,
        'Too big',
        fieldErrors: {
          'submission_file': 'The file may not be greater than 51200 kilobytes.'
        },
      );
      final errors = SubmissionFileErrors.of(error, fileCount: 1);
      expect(errors.perFile.single, contains('51200'));
    });

    test('too many files and unrelated errors', () {
      const tooMany = AppException(
        AppErrorKind.validation,
        'Too many',
        fieldErrors: {'submission_files': 'You may attach up to 10 files.'},
      );
      expect(SubmissionFileErrors.of(tooMany, fileCount: 2).files,
          'You may attach up to 10 files.');

      const general = AppException(AppErrorKind.forbidden, 'Not enrolled');
      expect(SubmissionFileErrors.of(general, fileCount: 0).general,
          'Not enrolled');
    });
  });

  group('widgets', () {
    testWidgets('a view-only file only offers View', (tester) async {
      await tester.pumpWidget(_app(LearningFileTile(
        file: LearningFileInfo({
          'id': 1,
          'name': 'guide.pdf',
          'downloadable': false,
          'download_path': '/lessons/1/files/1/download',
        }),
        downloadKey: 'lesson_1',
      )));
      await tester.pump();

      expect(find.text('View'), findsOneWidget);
      expect(find.bySemanticsLabel('View guide.pdf'), findsOneWidget);
      expect(find.text('Download'), findsNothing);
      expect(find.text('Open'), findsNothing);
      expect(find.textContaining('View only'), findsWidgets);
    });

    testWidgets('assignment card lists every instructions file',
        (tester) async {
      final a = AssignmentInfo({
        'id': 9,
        'title': 'Plan',
        'can_submit': true,
        'attachments': [
          {
            'id': 4,
            'name': 'brief.pdf',
            'downloadable': false,
            'download_path': '/a/9/4'
          },
          {
            'id': 5,
            'name': 'slides.pptx',
            'downloadable': false,
            'download_path': '/a/9/5'
          },
        ],
      }, now: DateTime(2026, 9, 30));
      await tester.pumpWidget(_app(
        AssignmentCard(
            assignment: a, onSubmit: () {}, onRequestExtension: () {}),
      ));
      await tester.pump();

      expect(find.text('Instructions files'), findsOneWidget);
      expect(find.text('brief.pdf'), findsOneWidget);
      expect(find.text('slides.pptx'), findsOneWidget);
      expect(find.text('View'), findsNWidgets(2));
      expect(find.text('Download'), findsNothing);
    });

    testWidgets('picked file row has a labelled remove button', (tester) async {
      var removed = false;
      await tester.pumpWidget(_app(PickedFileRow(
        name: 'essay.docx',
        sizeBytes: 4096,
        error: 'The file must be a PDF.',
        onRemove: () => removed = true,
      )));
      expect(find.text('The file must be a PDF.'), findsOneWidget);
      await tester.tap(find.byTooltip('Remove essay.docx'));
      expect(removed, isTrue);
    });
  });
}
