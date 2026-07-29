<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeToChamu;
use App\Models\AuditLog;
use App\Models\Bursary;
use App\Models\BursaryDocumentRequirement;
use App\Models\SiteVisit;
use App\Models\SocialPost;
use App\Models\SocialPostResponse;
use App\Models\User;
use App\Models\UserApplicationDocument;
use App\Models\UserApplicationProfile;
use App\Models\UserSubjectResult;
use App\Support\Social\FacebookGraph;
use App\Support\Social\InstagramGraph;
use App\Support\Social\LinkedInGraph;
use App\Support\Social\SocialImageStorage;
use App\Support\Social\SocialMediaConfig;
use App\Support\Social\ThreadsGraph;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Throwable;

class ToolController extends Controller
{
    public function index(Request $request)
    {
        $calculator = [
            'first_number' => $request->query('first_number'),
            'second_number' => $request->query('second_number'),
            'operation' => $request->query('operation', 'add'),
            'result' => null,
            'error' => null,
        ];

        if ($request->hasAny(['first_number', 'second_number'])) {
            $data = $request->validate([
                'first_number' => ['required', 'numeric'],
                'second_number' => ['required', 'numeric'],
                'operation' => ['required', 'in:add,subtract,multiply,divide'],
            ]);

            $firstNumber = (float) $data['first_number'];
            $secondNumber = (float) $data['second_number'];
            $operation = $data['operation'];

            $calculator['first_number'] = $data['first_number'];
            $calculator['second_number'] = $data['second_number'];
            $calculator['operation'] = $operation;

            if ($operation === 'divide' && $secondNumber == 0.0) {
                $calculator['error'] = 'Cannot divide by zero.';
            } else {
                $calculator['result'] = match ($operation) {
                    'add' => $firstNumber + $secondNumber,
                    'subtract' => $firstNumber - $secondNumber,
                    'multiply' => $firstNumber * $secondNumber,
                    'divide' => $firstNumber / $secondNumber,
                };
            }
        }

        return view('tools.index', [
            'calculator' => $calculator,
        ]);
            
    }
}
