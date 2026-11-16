<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Services\NumberGenerator;
use Illuminate\Database\Seeder;

/** 24 clearly fictional patients (names, numbers and addresses are invented). */
class PatientSeeder extends Seeder
{
    public function run(NumberGenerator $numbers): void
    {
        if (Patient::exists()) {
            return;
        }

        $doctors = Doctor::orderBy('id')->pluck('id')->all();
        $attendant = User::where('username', 'attendant1')->firstOrFail();

        $people = [
            ['Aaron Mkandawire', '1985-03-14', 'male'], ['Beatrice Gondwe', '1992-07-02', 'female'],
            ['Chimwemwe Zulu', '2001-11-23', 'female'], ['Dalitso Kachingwe', '1978-01-30', 'male'],
            ['Esnart Lungu', '1966-09-09', 'female'], ['Fumbani Moyo', '2015-05-17', 'male'],
            ['Gift Kumwenda', '1989-12-05', 'male'], ['Hope Chisale', '1995-04-21', 'female'],
            ['Isaac Mhango', '1958-08-12', 'male'], ['Joyce Ngwira', '1983-02-26', 'female'],
            ['Kondwani Jere', '1999-10-10', 'male'], ['Lucy Kalua', '1974-06-18', 'female'],
            ['Memory Nkhoma', '2008-03-03', 'female'], ['Noel Simbeye', '1991-09-29', 'male'],
            ['Olipa Chavula', '1987-07-07', 'female'], ['Patrick Msowoya', '1970-12-19', 'male'],
            ['Queen Mtambo', '2012-01-15', 'female'], ['Robert Salima', '1963-05-25', 'male'],
            ['Sarah Mbewe', '1996-08-08', 'female'], ['Thoko Katunga', '1981-11-11', 'female'],
            ['Upile Chilima', '2003-02-14', 'female'], ['Victor Manda', '1976-04-04', 'male'],
            ['Wezzie Tchale', '1990-10-31', 'female'], ['Yamikani Dzimbiri', '2018-06-06', 'male'],
        ];

        $areas = ['Area 25, Lilongwe', 'Chilomoni, Blantyre', 'Mzuzu City Centre', 'Zomba Town', 'Kanengo, Lilongwe', 'Ndirande, Blantyre'];

        foreach ($people as $i => [$name, $dob, $gender]) {
            Patient::create([
                'patient_number' => $numbers->next('patient', 'CC-', 6),
                'full_name' => $name.' (DEMO)',
                'date_of_birth' => $dob,
                'gender' => $gender,
                'address' => $areas[$i % count($areas)].' – fictional address',
                'phone' => sprintf('+265 888 000 %03d', $i + 1),
                'next_of_kin_name' => 'Next of Kin of '.explode(' ', $name)[0].' (DEMO)',
                'next_of_kin_phone' => sprintf('+265 999 000 %03d', $i + 1),
                'primary_doctor_id' => $doctors[$i % count($doctors)],
                'registered_on' => today()->subDays(60 - $i)->toDateString(),
                'registered_by' => $attendant->id,
            ]);
        }
    }
}
