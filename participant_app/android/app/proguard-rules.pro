# R8 rules for the release build (minify + shrink resources are on in build.gradle.kts).
# The Flutter Gradle plugin already adds Flutter's own rules; these cover plugins.

# Flutter references Play Core classes for deferred components, which this app does not use.
-dontwarn com.google.android.play.core.**

# flutter_local_notifications stores scheduled notifications with Gson and reflection.
-keep class com.dexterous.** { *; }
-keep class com.google.gson.reflect.TypeToken { *; }
-keep class * extends com.google.gson.reflect.TypeToken
-keepattributes Signature
-keepattributes *Annotation*

# Firebase Cloud Messaging service and receivers are referenced from the merged manifest.
-keep class io.flutter.plugins.firebase.messaging.** { *; }
