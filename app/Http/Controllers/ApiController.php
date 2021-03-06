<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\StudentWork;
use App\Model\Work;
use App\Model\Student;
use App\Model\StudentNote;
use App\Model\StudentCard;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{

    public function login(Request $request) {
        $login = $request->validate([
            "email" => "required:string",
            "password" => "required"
        ]);

        if ( !Auth::attempt( $login ) ) {
            return response()->json([
                "logged" => false,
                "message" => "Kullanıcı adı veya şifre hatalı"
            ]);
        }
        //return Auth::user();
        $accessToken = Auth::user()->createToken('authToken')->accessToken;

        return response()->json([
            "logged" => true,
            "user" => Auth::user(),
            "access_token" => $accessToken
        ]);
    }

    public function allStudents() {
    	$students = Student::where('statu', 1)->paginate(15);
    	return response()->json($students);
    }

    public function studentCard($student_no) {
    	$student_card = Student::where('no', $student_no)->with('student_card')->with("student_works")->first();

    	if ( $student_card )
    		return response()->json($student_card);
    	else
    		return response()->json("No data");
    }

    public function studentWorks($student_no) {
        $student = Student::where('no', $student_no)->with('student_works')->first();

        if ( $student )
            return response()->json($student);
        else
            return response()->json("No data");
    }

    public function studentWorkNote($student_no, $work_no) {
        $student = StudentWork::where('student_no', $student_no)->first();

        if ( $student ) {
            $student_note = StudentNote::where('work_no', $work_no)->first();
            if ( $student_note ) {
                return response()->json($student_note);
            } else {
                return response()->json("Ders notu henüz girilmemiş");
            }
        } else {
            return response()->json("Böyle bir ders alan öğrenci yok.");
        }
    }

    public function studentWorkNoteCreate(Faker $faker) {
        $student_no = request()->header('student_no');
        $work_no = request()->header('work_no');

        $student_works = StudentWork::where('student_no', $student_no)->where('student_work', $work_no)->first();

        if (!$student_works)
            return response()->json("No data");

        //return $student_works;

        $insert = [
            "student_notes_no" => $faker->creditCardNumber,
            "student_no" => $student_works->student_no,
            "exam" => request("exam"),
            "final" => request("final"),
            "average" => null,
            "task" => request("task"),
            "case" => null,
            "work_no" => $student_works->student_work
        ];

        if ( !$insert["task"] )
            $insert["average"] = ( $insert["exam"] * ( 30 / 100 ) ) + ( $insert["final"] * ( 70 / 100 ) );
        else
            $insert["average"] = ( $insert["exam"] * ( 30 / 100 ) ) + ( $insert["final"] * ( 50 / 100 ) ) + ( $insert["task"] * ( 20 / 100 ) );

        if ( $insert["average"] >= 45 )
            $insert["case"] = 1;
        else
            $insert["case"] = 0;

        $create = StudentNote::create($insert);

        if ( $create ) {
            return response()->json($create);
        } else {
            return response()->json("Data kayıt edilemedi", $insert);
        }

    }

    public function allWorks() {
        $works = Work::paginate(15);
        return response()->json($works);
    }

    public function workCards($work_no) {
        $student_work = StudentWork::where("student_work", $work_no)->first();
        $work = Work::where("no", $student_work->work_no)->first();
        return response()->json($work);
    }
}
