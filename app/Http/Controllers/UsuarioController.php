<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class UsuarioController extends Controller
{
    public function create()
    {
        return view('register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cpf' => 'required|string|max:11',
            'photo' => 'nullable|image',
            'captured_image' => 'nullable|string'
        ]);

        try {
            if ($request->has('captured_image') && $request->captured_image) {
                // Processar a imagem capturada da câmera
                $imageData = $request->captured_image;
                $imageData = str_replace('data:image/png;base64,', '', $imageData);
                $imageData = str_replace(' ', '+', $imageData);
                $imageData = base64_decode($imageData);
                $fileName = uniqid() . '.png';
                $filePath = storage_path('app/public/photos/' . $fileName);
                file_put_contents($filePath, $imageData);
                $path = 'public/photos/' . $fileName;
            } else {
                // Processar a imagem carregada
                $path = $request->file('photo')->store('public/photos');
            }

            $usuario = Usuario::create([
                'name' => $request->name,
                'cpf' => $request->cpf,
                'photo' => $path
            ]);

            Log::info('Photo uploaded and user created', ['user' => $usuario->toArray()]);

            // Processar a imagem com Python
            $imagePath = storage_path('app/' . $path);
            $faceData = $this->processImageWithPython($imagePath);

            // Salvar os dados faciais no banco de dados
            if (!empty($faceData)) {
                $usuario->face_data = json_encode($faceData);
                $usuario->save();
                Log::info('Face data saved', ['face_data' => $usuario->face_data]);
            } else {
                Log::error('No face data returned from Python script');
            }

            return redirect()->route('usuarios.create')->with('success', 'Usuário cadastrado com sucesso!');
        } catch (Exception $e) {
            Log::error('Error in user registration', ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return redirect()->route('usuarios.create')->with('error', 'Erro ao cadastrar usuário: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        }
    }

    public function showConsultForm()
    {
        return view('consult');
    }

    public function consult(Request $request)
    {
        $data = $request->get('image');
        $data = str_replace('data:image/png;base64,', '', $data);
        $data = str_replace(' ', '+', $data);
        $imageData = base64_decode($data);

        try {
            Log::info('Received image for consultation', ['image_length' => strlen($data)]);

            // Salva a imagem recebida
            $imagePath = storage_path('app/public/photos/temp.jpg');
            file_put_contents($imagePath, $imageData);
            Log::info('Image saved for consultation', ['path' => $imagePath]);

            // Processar a imagem com Python
            $facesDetected = $this->processImageWithPython($imagePath);
            Log::info('Faces detected', ['faces_detected' => $facesDetected]);

            // Verifica se as faces detectadas correspondem aos usuários cadastrados
            $usuarios = Usuario::all();
            $names = [];

            foreach ($usuarios as $usuario) {
                $userFaceData = json_decode($usuario->face_data, true);
                Log::info('Comparing with user', ['user' => $usuario->name, 'face_data' => $userFaceData]);

                if (is_array($userFaceData) && is_array($facesDetected)) {
                    foreach ($facesDetected as $face) {
                        foreach ($userFaceData as $userFace) {
                            if ($this->compareFaces($face, $userFace)) {
                                $names[] = [
                                    'name' => $usuario->name,
                                    'x' => $face['x'],
                                    'y' => $face['y']
                                ];
                                Log::info('Face match found', ['user' => $usuario->name, 'coordinates' => $names]);
                            }
                        }
                    }
                } else {
                    Log::warning('Invalid face data for comparison', ['userFaceData' => $userFaceData, 'facesDetected' => $facesDetected]);
                }
            }

            return response()->json(['names' => $names]);
        } catch (Exception $e) {
            Log::error('Error in face consultation', ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json(['error' => 'Erro ao consultar usuário: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()], 500);
        }
    }

    private function processImageWithPython($imagePath)
    {
        $pythonPath = base_path('myenv/Scripts/python.exe');
        Log::info('Using Python Path: ' . $pythonPath);
        Log::info('Executing script: ' . base_path('process_image.py') . ' with image: ' . $imagePath);

        $process = new Process([$pythonPath, base_path('process_image.py'), $imagePath]);
        $process->run();

        // Capturar toda a saída para depuração
        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();
        Log::info('Python process output: ' . $output);
        Log::error('Python process error output: ' . $errorOutput);

        // Verifica se o processo falhou
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // Retorna os dados da face detectada
        $faceData = json_decode($output, true);

        // Adicione logs detalhados para verificar os dados decodificados
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON decode error: ' . json_last_error_msg());
        } else {
            Log::info('Decoded face data', ['face_data' => $faceData]);
        }

        // Forçar a verificação manual do JSON
        if (is_null($faceData) && strpos($output, '[') !== false && strpos($output, ']') !== false) {
            $faceData = json_decode(trim($output), true);
            Log::info('Manually decoded face data', ['face_data' => $faceData]);
        }

        return $faceData;
    }

    private function compareFaces($face1, $face2)
    {
        if (!is_array($face1) || !is_array($face2)) {
            Log::error('Invalid face data structure in compareFaces', ['face1' => $face1, 'face2' => $face2]);
            return false;
        }

        $threshold = 0.6; // Ajuste este valor conforme necessário
        $distance = $this->calculateEuclideanDistance($face1, $face2);

        return $distance < $threshold;
    }
}
