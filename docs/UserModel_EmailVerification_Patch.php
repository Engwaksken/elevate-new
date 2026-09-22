<?php
// app/Models/User.php
// Ensure the model implements MustVerifyEmail.

use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    // Keep the existing User model properties/traits/relationships.
}
