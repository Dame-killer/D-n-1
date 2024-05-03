<?php

namespace App\Http\Controllers\AA;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class PointController extends Controller
{
    function indexClass(){
        $classes = DB::table('classes')
        ->paginate(10);
        return view('academic_affairs.points.index_class', ['classes' => $classes]);
    }

    function indexSubject(Request $request){
        $class_id = $request->input('class_id');
        $class_subjects = DB::table('class_subjects')
            ->where('class_subjects.class_id', '=', $class_id)
            ->join('classes', 'class_subjects.class_id', '=', 'classes.class_id')
            ->join('subjects', 'class_subjects.subject_id', '=', 'subjects.subject_id')
            ->select('classes.*', 'subjects.*', 'class_subjects.*')
            ->paginate(10);
        $classes = DB::table('classes')->get();
        $subjects = DB::table('subjects')->get();
        return view('academic_affairs.points.index_subject', ['class_subjects' => $class_subjects, 'classes' => $classes, 'subjects' => $subjects]);
    }

    function indexPoint(Request $request){
        $cs_id = $request->input('cs_id');
        $points = DB::table('points')
            ->join('class_subject_students', 'points.css_id', '=', 'class_subject_students.css_id')
            ->join('users', 'class_subject_students.id', '=', 'users.id')
            ->join('class_subjects', 'class_subject_students.cs_id', '=', 'class_subjects.cs_id')
            ->join('classes', 'class_subjects.class_id', '=', 'classes.class_id')
            ->join('subjects', 'class_subjects.subject_id', '=', 'subjects.subject_id')
            ->join('education_programs', 'subjects.ep_id', '=', 'education_programs.ep_id')
            ->where('class_subject_students.cs_id', '=', $cs_id)
            ->select('points.*', 'class_subject_students.*', 'users.*', 'class_subjects.*', 'classes.*', 'education_programs.*', 'subjects.*')->get();
        $class_subject_students = DB::table('class_subject_students')
        ->join('users', 'class_subject_students.id', '=', 'users.id')
        ->join('class_subjects', 'class_subject_students.cs_id', '=', 'class_subjects.cs_id')
        ->join('classes', 'class_subjects.class_id', '=', 'classes.class_id')
        ->join('subjects', 'class_subjects.subject_id', '=', 'subjects.subject_id')
        ->join('majors', 'subjects.major_id', '=', 'majors.major_id' )
        ->select('users.*','class_subject_students.*', 'class_subjects.*' , 'classes.*', 'subjects.*', 'majors.*')
        ->where('class_subjects.cs_id','=', $cs_id)
        ->get();
        $users = DB::table('users')->get();
        $class_subjects = DB::table('class_subjects')
            ->join('classes', 'class_subjects.class_id', '=', 'classes.class_id')
            ->join('subjects', 'class_subjects.subject_id', '=', 'subjects.subject_id')
            ->join('education_programs', 'subjects.ep_id', '=', 'education_programs.ep_id')
            ->where('class_subjects.cs_id', '=', $cs_id)
            ->select('class_subjects.*', 'classes.*', 'subjects.*', 'education_programs.*')
            ->get();
        $education_programs = DB::table('education_programs')->get();

        $classes = DB::table('classes')->get();
        $subjects = DB::table('subjects')->get();
        return view('academic_affairs.points.index_point', ['class_subject_students' => $class_subject_students, 'class_subjects' => $class_subjects, 'users' => $users, 'points' => $points, 'classes' => $classes, 'subjects' => $subjects, 'education_programs' => $education_programs]);
    }

    function createStudent(Request $request){
        $css_id = $request->input('css');
        $validator = Validator::make($request->all(), [
            'css_id' => [
                'required',
                'css_id',
                Rule::unique('points')
            ],
        ]);
        if ($validator->fails()) {
            flash()->addError('Sinh viên đã được thêm!');
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $result = DB::table('points')
        ->join('class_subject_students', 'points.css_id', '=', 'class_subject_students.css_id')
        ->join('users', 'class_subject_students.id', '=', 'users.id')
        ->join('class_subjects', 'class_subject_students.cs_id', '=', 'class_subjects.cs_id')
        ->join('classes', 'class_subjects.class_id', '=', 'classes.class_id')
        ->join('subjects', 'class_subjects.subject_id', '=', 'subjects.subject_id')
        ->join('education_programs', 'subjects.ep_id', '=', 'education_programs.ep_id')
        ->select('points.*', 'class_subject_students.*', 'users.*', 'class_subjects.*', 'classes.*', 'education_programs.*')
        ->insert([
                'css_id' => $css_id,
            ]);
        if($result){
            flash()->addSuccess('Thêm thành công!');
            // return redirect()->route('aa-point-point');
            return redirect()->back();
        }else {
            flash()->addError('Thêm thất bại!');
            return redirect()->route('aa-point-point');
        }
    }

    function edit(Request $request){
        $point_id = $request->input('point_id');
        $points = DB::table('points')
            ->join('class_subject_students', 'points.css_id', '=', 'class_subject_students.css_id')
            ->join('users', 'class_subject_students.id', '=', 'users.id')
            ->join('class_subjects', 'class_subject_students.cs_id', '=', 'class_subjects.cs_id')
            ->where('points.point_id', '=', $point_id)
            ->select('points.*', 'class_subject_students.*', 'users.*', 'class_subjects.*')->get();
        $class_subject_students = DB::table('class_subject_students')->get();
        $users = DB::table('users')->get();
        $class_subjects = DB::table('class_subjects')->get();
        return view('academic_affairs.points.edit_point', ['points' => $points, 'class_subject_students' => $class_subject_students, 'class_subjects' => $class_subjects, 'users' => $users]);
    }


    public function saveData(Request $request)
    {
        $pointId = $request->input('pointId');
        $theory = $request->input('theory');
        $practice = $request->input('practice');
        $asm = $request->input('asm');
        // $result = (($theory >= 5 && $practice == null && $asm == null)
        // || ($practice >= 5 && $theory == null && $asm == null)
        // || ($asm >= 5 && $theory == null && $practice == null)
        // || ($theory >= 5 && $practice >= 5 && $asm == null)) ? 1 : 0;

        if (($theory < 5 && $theory >= 0) || ($practice < 5 && $practice >= 0) || ($asm < 5 && $asm >= 0)) {
            $result = 0;
        } elseif ($theory === null && $practice === null && $asm === null) {
            $result = 2;
        } elseif (($theory >= 5 && $practice == null && $asm == null)
        || ($practice >= 5 && $theory == null && $asm == null)
        || ($asm >= 5 && $theory == null && $practice == null)
        || ($theory >= 5 && $practice >= 5 && $asm == null)) {
            $result = 1;
        }

        DB::table('points')
            ->where('point_id', $pointId)
            ->update([
                'theory' => $theory,
                'practice' => $practice,
                'asm' => $asm,
                'result' => $result,
            ]);

        return response()->json(['success' => true]);
    }
}
