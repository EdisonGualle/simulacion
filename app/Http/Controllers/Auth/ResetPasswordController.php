<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use App\Models\PasswordResetToken;
use App\Jobs\Auth\SendPasswordResetEmail;

class ResetPasswordController extends Controller
{
    public function resetPassword(Request $request)
    {
        try {
            $user = User::where('name', $request->name)
                ->orWhere('email', $request->email)
                ->first(['name', 'email']);

            if ($user && isset($user->email)) {
                $token = Str::random(60);

                // Construir la URL con el token
                $domain = URL::to('/');
                $url = $domain . '/reset-password?token=' . $token;

                // Configurar datos para el correo
                $data = [
                    'url' => $url,
                    'email' => $user->email,
                    'title' => "Recuperar Contraseña",
                    'body' => 'Por favor, haz clic en el enlace para restablecer tu contraseña:'
                ];

                // Despachar el Job para enviar el correo en segundo plano
                SendPasswordResetEmail::dispatch($data);

                $now = Carbon::now()->format('Y-m-d H:i:s');

                $existingToken = PasswordResetToken::where('email', $user->email)->first();

                if ($existingToken) {
                    $existingToken->update([
                        'token' => $token,
                        'created_at' => $now
                    ]);
                } else {
                    PasswordResetToken::create([
                        'email' => $user->email,
                        'token' => $token,
                        'created_at' => $now,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Hemos enviado un enlace para restablecer tu contraseña a la dirección de correo registrada.'
                ]);
            } else {
                return response()->json(['success' => false, 'message' => 'Credenciales inválidas.']);
            }
        } catch (\Exception $e) {
            // Manejar excepciones y devolver una respuesta JSON
            return response()->json(['success' => false, 'message' => 'Error interno del servidor.'], 500);
        }
    }

    // Cargar vista para restablecer contraseña
    public function loadResetPasswordView(Request $request)
    {
        $resetData = PasswordResetToken::where('token', $request->token)->first();

        if ($resetData) {
            $user = User::where('email', $resetData->email)->first();

            if ($user) {
                return view('passwordReset', compact('user'));
            } else {
                return view('404');
            }
        } else {
            return view('404');
        }
    }

    // Funcionalidad para restablecer la contraseña
    public function updatePassword(Request $request)
    {
        // Verifica si el formulario se envió correctamente
        if ($request->isMethod('post')) {
            $request->validate([
                'password' => 'required|string|min:6|confirmed',
            ]);

            $user = User::find($request->id);

            if ($user) {
                // Actualiza la contraseña del usuario con el hash
                $user->password = Hash::make($request->password);
                $user->save();

                PasswordResetToken::where('email', $user->email)->delete();

                return response('<div style="text-align: center; background-color: #f3f4f6; padding: 20px; border-radius: 10px;"><h1 style="color: #333;">Tu contraseña se ha restablecido exitosamente.</h1></div>');
            } else {

                return response()->json(['successful' => false, 'error' => 'Usuario no encontrado'], 404);
            }
        }
    }
}