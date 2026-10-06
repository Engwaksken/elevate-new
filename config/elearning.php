<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Participant download policy
    |--------------------------------------------------------------------------
    |
    | Lesson and assignment files whose extension is listed here may be
    | downloaded by participants. Every other course file (PDF, Word,
    | PowerPoint, images, audio, video, text...) is view-only inside the
    | platform: it is served inline and never as an attachment. Course staff
    | (instructors/trainers of the course and e-learning administrators)
    | always keep full download access, and participants can always download
    | the files they submitted themselves.
    |
    */

    'downloadable_extensions' => ['xlsx', 'xls', 'csv', 'zip'],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    */

    // Maximum number of files accepted in one upload (lesson, assignment or submission).
    'max_files_per_upload' => 10,

    // Per-file size limit in kilobytes (50 MB).
    'max_file_kb' => 51200,

    // Disk new lesson/assignment materials and submissions are stored on (private).
    'disk' => 'local',

    'lesson_mimes' => 'pdf,doc,docx,ppt,pptx,xls,xlsx,csv,txt,zip,jpg,jpeg,png,webp,mp3,mp4,m4a',

    'assignment_mimes' => 'pdf,doc,docx,ppt,pptx,xls,xlsx,csv,txt,zip,jpg,jpeg,png,webp',

    'submission_mimes' => 'pdf,doc,docx,ppt,pptx,xls,xlsx,csv,txt,zip,jpg,jpeg,png,webp',

];
