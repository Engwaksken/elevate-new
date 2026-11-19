<?php

use App\Http\Controllers\Chat\ConversationController;
use Illuminate\Support\Facades\Route;

/*
 * Chat routes, registered twice from web.php: under /participant (names
 * participant.chat.*) and under /admin for staff (names admin.chat.*).
 * The controller picks the layout and links from the route name.
 */
Route::get('/chat', [ConversationController::class, 'index'])->name('chat.index');
Route::get('/chat/people', [ConversationController::class, 'people'])->middleware('throttle:120,1')->name('chat.people');
Route::post('/chat', [ConversationController::class, 'store'])->name('chat.store');
Route::put('/chat/{conversation}', [ConversationController::class, 'updateConversation'])->whereNumber('conversation')->name('chat.update');
Route::delete('/chat/{conversation}', [ConversationController::class, 'destroyConversation'])->whereNumber('conversation')->name('chat.destroy');
Route::get('/chat/{conversation}', [ConversationController::class, 'show'])->whereNumber('conversation')->name('chat.show');
Route::get('/chat/{conversation}/messages', [ConversationController::class, 'messages'])->whereNumber('conversation')->name('chat.messages.index');
Route::post('/chat/{conversation}/messages', [ConversationController::class, 'send'])->whereNumber('conversation')->name('chat.messages.store');
Route::put('/chat/{conversation}/messages/{message}', [ConversationController::class, 'updateMessage'])->whereNumber('conversation')->whereNumber('message')->name('chat.messages.update');
Route::delete('/chat/{conversation}/messages/{message}', [ConversationController::class, 'destroyMessage'])->whereNumber('conversation')->whereNumber('message')->name('chat.messages.destroy');
Route::get('/chat/{conversation}/attachments/{attachment}', [ConversationController::class, 'attachment'])->whereNumber('conversation')->whereNumber('attachment')->name('chat.attachments.show');
Route::post('/chat/{conversation}/invite', [ConversationController::class, 'invite'])->whereNumber('conversation')->name('chat.invite');
Route::post('/chat/{conversation}/leave', [ConversationController::class, 'leave'])->whereNumber('conversation')->name('chat.leave');
