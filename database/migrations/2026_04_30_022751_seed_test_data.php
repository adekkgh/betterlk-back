<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // --- РОЛИ ---
        $roles = DB::table('roles')->pluck('id', 'name');

        // --- СПЕЦИАЛИЗАЦИИ ---
        $specIds = [];
        $specs = [
            'Информационные системы',
            'Прикладная математика',
            'Информационные вычислительные технологии',
            'Программная инженерия',
            'Кибербезопасность',
        ];
        foreach ($specs as $name) {
            $existing = DB::table('specializations')->where('name', $name)->first();
            $specIds[$name] = $existing
                ? $existing->id
                : DB::table('specializations')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }

        // --- ГРУППЫ ---
        $groupIds = [];
        $groups = [
            ['name' => 'ИСП-101', 'course' => 1, 'spec' => 'Информационные системы'],
            ['name' => 'ИСП-201', 'course' => 2, 'spec' => 'Информационные системы'],
            ['name' => 'ИСП-301', 'course' => 3, 'spec' => 'Информационные системы'],
            ['name' => 'ПМ-101',  'course' => 1, 'spec' => 'Прикладная математика'],
            ['name' => 'ПМ-201',  'course' => 2, 'spec' => 'Прикладная математика'],
            ['name' => 'ИВТ-101', 'course' => 1, 'spec' => 'Информационные вычислительные технологии'],
            ['name' => 'ИВТ-201', 'course' => 2, 'spec' => 'Информационные вычислительные технологии'],
            ['name' => 'ПИ-301',  'course' => 3, 'spec' => 'Программная инженерия'],
            ['name' => 'КБ-201',  'course' => 2, 'spec' => 'Кибербезопасность'],
        ];
        foreach ($groups as $g) {
            $existing = DB::table('groups')->where('name', $g['name'])->first();
            $groupIds[$g['name']] = $existing
                ? $existing->id
                : DB::table('groups')->insertGetId([
                    'name'               => $g['name'],
                    'course'             => $g['course'],
                    'specialization_id'  => $specIds[$g['spec']],
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
        }

        // --- ПРЕДМЕТЫ ---
        $subjectIds = [];
        $subjects = [
            'Математический анализ',
            'Линейная алгебра',
            'Теория вероятностей и статистика',
            'Дискретная математика',
            'Базы данных',
            'Операционные системы',
            'Сети и телекоммуникации',
            'Веб-разработка',
            'Алгоритмы и структуры данных',
            'Машинное обучение',
            'Анализ данных',
            'Объектно-ориентированное программирование',
            'Информационная безопасность',
            'Компьютерная графика',
        ];
        foreach ($subjects as $name) {
            $existing = DB::table('subjects')->where('name', $name)->first();
            $subjectIds[$name] = $existing
                ? $existing->id
                : DB::table('subjects')->insertGetId([
                    'name'       => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        // --- ПРЕПОДАВАТЕЛИ ---
        $profIds = [];
        $professors = [
            ['name' => 'Иванов Дмитрий Сергеевич',    'email' => 'ivanov@university.ru'],
            ['name' => 'Петрова Елена Александровна',  'email' => 'petrova@university.ru'],
            ['name' => 'Сидоров Михаил Викторович',    'email' => 'sidorov@university.ru'],
            ['name' => 'Козлова Анна Николаевна',      'email' => 'kozlova@university.ru'],
            ['name' => 'Новиков Андрей Павлович',      'email' => 'novikov@university.ru'],
        ];
        foreach ($professors as $p) {
            $existing = DB::table('users')->where('email', $p['email'])->first();
            if ($existing) {
                $profIds[$p['name']] = $existing->id;
            } else {
                $profIds[$p['name']] = DB::table('users')->insertGetId([
                    'name'               => $p['name'],
                    'email'              => $p['email'],
                    'password'           => Hash::make('password123'),
                    'role_id'            => $roles['professor'],
                    'email_verified_at'  => now(),
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
                DB::table('professor_profiles')->insertOrIgnore([
                    'user_id'    => $profIds[$p['name']],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Привязка преподавателей к группам
        $profGroups = [
            'Иванов Дмитрий Сергеевич'    => ['ИСП-101', 'ИСП-201', 'ИСП-301'],
            'Петрова Елена Александровна'  => ['ПМ-101', 'ПМ-201', 'ИСП-201'],
            'Сидоров Михаил Викторович'    => ['ИВТ-101', 'ИВТ-201', 'ПИ-301'],
            'Козлова Анна Николаевна'      => ['КБ-201', 'ИСП-301', 'ПИ-301'],
            'Новиков Андрей Павлович'      => ['ПМ-101', 'ПМ-201', 'ИВТ-101'],
        ];
        foreach ($profGroups as $profName => $groups) {
            foreach ($groups as $groupName) {
                DB::table('professor_groups')->insertOrIgnore([
                    'professor_id' => $profIds[$profName],
                    'group_id'     => $groupIds[$groupName],
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        // --- СТУДЕНТЫ ---
        $studentData = [
            // ИСП-301
            ['name' => 'Алексеев Кирилл Денисович',    'email' => 'alekseev@student.ru',  'group' => 'ИСП-301'],
            ['name' => 'Борисова Мария Игоревна',       'email' => 'borisova@student.ru',   'group' => 'ИСП-301'],
            ['name' => 'Волков Никита Андреевич',       'email' => 'volkov@student.ru',     'group' => 'ИСП-301'],
            ['name' => 'Громова Дарья Сергеевна',       'email' => 'gromova@student.ru',    'group' => 'ИСП-301'],
            ['name' => 'Денисов Артём Олегович',        'email' => 'denisov@student.ru',    'group' => 'ИСП-301'],
            ['name' => 'Ефимова Полина Вячеславовна',   'email' => 'efimova@student.ru',    'group' => 'ИСП-301'],
            // ИСП-201
            ['name' => 'Жуков Роман Дмитриевич',        'email' => 'zhukov@student.ru',     'group' => 'ИСП-201'],
            ['name' => 'Зайцева Виктория Андреевна',    'email' => 'zaitseva@student.ru',   'group' => 'ИСП-201'],
            ['name' => 'Иванченко Глеб Николаевич',     'email' => 'ivanchenko@student.ru', 'group' => 'ИСП-201'],
            ['name' => 'Карпова Светлана Олеговна',     'email' => 'karpova@student.ru',    'group' => 'ИСП-201'],
            // ИСП-101
            ['name' => 'Лебедев Максим Павлович',       'email' => 'lebedev@student.ru',    'group' => 'ИСП-101'],
            ['name' => 'Макарова Ирина Сергеевна',      'email' => 'makarova@student.ru',   'group' => 'ИСП-101'],
            ['name' => 'Николаев Владислав Артёмович',  'email' => 'nikolaev@student.ru',   'group' => 'ИСП-101'],
            // ПМ-201
            ['name' => 'Орлова Анастасия Денисовна',    'email' => 'orlova@student.ru',     'group' => 'ПМ-201'],
            ['name' => 'Павлов Евгений Максимович',     'email' => 'pavlov@student.ru',     'group' => 'ПМ-201'],
            ['name' => 'Романова Екатерина Игоревна',   'email' => 'romanova@student.ru',   'group' => 'ПМ-201'],
            // ПМ-101
            ['name' => 'Смирнов Даниил Андреевич',      'email' => 'smirnov@student.ru',    'group' => 'ПМ-101'],
            ['name' => 'Тихонова Алина Викторовна',     'email' => 'tikhonova@student.ru',  'group' => 'ПМ-101'],
            // ИВТ-201
            ['name' => 'Фёдоров Илья Романович',        'email' => 'fedorov@student.ru',    'group' => 'ИВТ-201'],
            ['name' => 'Харитонова Юлия Дмитриевна',    'email' => 'kharitonova@student.ru','group' => 'ИВТ-201'],
            ['name' => 'Цветков Антон Сергеевич',       'email' => 'tsvetkov@student.ru',   'group' => 'ИВТ-201'],
            // ИВТ-101
            ['name' => 'Чернова Валерия Олеговна',      'email' => 'chernova@student.ru',   'group' => 'ИВТ-101'],
            ['name' => 'Шестаков Игорь Павлович',       'email' => 'shestakov@student.ru',  'group' => 'ИВТ-101'],
            // ПИ-301
            ['name' => 'Щукина Наталья Андреевна',      'email' => 'shchukina@student.ru',  'group' => 'ПИ-301'],
            ['name' => 'Яковлев Константин Денисович',  'email' => 'yakovlev@student.ru',   'group' => 'ПИ-301'],
            ['name' => 'Абрамова Татьяна Сергеевна',    'email' => 'abramova@student.ru',   'group' => 'ПИ-301'],
            // КБ-201
            ['name' => 'Беляев Станислав Романович',    'email' => 'belyaev@student.ru',    'group' => 'КБ-201'],
            ['name' => 'Васильева Диана Николаевна',    'email' => 'vasilyeva@student.ru',  'group' => 'КБ-201'],
            ['name' => 'Григорьев Тимур Олегович',      'email' => 'grigoryev@student.ru',  'group' => 'КБ-201'],
        ];

        $studentIds = [];
        foreach ($studentData as $s) {
            $existing = DB::table('users')->where('email', $s['email'])->first();
            if ($existing) {
                $studentIds[$s['email']] = $existing->id;
            } else {
                $studentId = DB::table('users')->insertGetId([
                    'name'              => $s['name'],
                    'email'             => $s['email'],
                    'password'          => Hash::make('password123'),
                    'role_id'           => $roles['student'],
                    'email_verified_at' => now(),
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
                DB::table('student_profiles')->insertOrIgnore([
                    'user_id'    => $studentId,
                    'group_id'   => $groupIds[$s['group']],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $studentIds[$s['email']] = $studentId;
            }
        }

        // --- ЖУРНАЛЫ ---
        $journals = [
            // ИСП-301, 1 семестр 2024
            ['subject' => 'Базы данных',           'group' => 'ИСП-301', 'prof' => 'Иванов Дмитрий Сергеевич',   'sem' => 1, 'year' => 2024],
            ['subject' => 'Веб-разработка',         'group' => 'ИСП-301', 'prof' => 'Козлова Анна Николаевна',    'sem' => 1, 'year' => 2024],
            ['subject' => 'Алгоритмы и структуры данных', 'group' => 'ИСП-301', 'prof' => 'Сидоров Михаил Викторович', 'sem' => 1, 'year' => 2024],
            // ИСП-201, 1 семестр 2024
            ['subject' => 'Операционные системы',   'group' => 'ИСП-201', 'prof' => 'Иванов Дмитрий Сергеевич',   'sem' => 1, 'year' => 2024],
            ['subject' => 'Сети и телекоммуникации','group' => 'ИСП-201', 'prof' => 'Петрова Елена Александровна','sem' => 1, 'year' => 2024],
            // ИСП-101, 1 семестр 2024
            ['subject' => 'Математический анализ',  'group' => 'ИСП-101', 'prof' => 'Петрова Елена Александровна','sem' => 1, 'year' => 2024],
            ['subject' => 'Дискретная математика',   'group' => 'ИСП-101', 'prof' => 'Новиков Андрей Павлович',    'sem' => 1, 'year' => 2024],
            // ПМ-201, 1 семестр 2024
            ['subject' => 'Теория вероятностей и статистика', 'group' => 'ПМ-201', 'prof' => 'Петрова Елена Александровна', 'sem' => 1, 'year' => 2024],
            ['subject' => 'Линейная алгебра',        'group' => 'ПМ-201', 'prof' => 'Новиков Андрей Павлович',    'sem' => 1, 'year' => 2024],
            // ПИ-301, 1 семестр 2024
            ['subject' => 'Машинное обучение',       'group' => 'ПИ-301', 'prof' => 'Сидоров Михаил Викторович',  'sem' => 1, 'year' => 2024],
            ['subject' => 'Анализ данных',           'group' => 'ПИ-301', 'prof' => 'Козлова Анна Николаевна',    'sem' => 1, 'year' => 2024],
            // КБ-201, 1 семестр 2024
            ['subject' => 'Информационная безопасность', 'group' => 'КБ-201', 'prof' => 'Козлова Анна Николаевна', 'sem' => 1, 'year' => 2024],
        ];

        $journalIds = [];
        foreach ($journals as $j) {
            $existing = DB::table('journals')
                ->where('subject_id', $subjectIds[$j['subject']])
                ->where('group_id',   $groupIds[$j['group']])
                ->where('semester',   $j['sem'])
                ->where('year',       $j['year'])
                ->first();

            $jid = $existing
                ? $existing->id
                : DB::table('journals')->insertGetId([
                    'subject_id'   => $subjectIds[$j['subject']],
                    'group_id'     => $groupIds[$j['group']],
                    'professor_id' => $profIds[$j['prof']],
                    'semester'     => $j['sem'],
                    'year'         => $j['year'],
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

            $journalIds[] = [
                'id'    => $jid,
                'group' => $j['group'],
            ];
        }

        // --- ЗАПИСИ В ЖУРНАЛАХ ---
        // Генерируем реалистичные записи для нескольких журналов
        $groupStudents = [];
        foreach ($studentData as $s) {
            $groupStudents[$s['group']][] = $studentIds[$s['email']];
        }

        // Даты занятий по средам и пятницам сентябрь-ноябрь 2024
        $lessonDates = [];
        $cur = new \DateTime('2024-09-04');
        $end = new \DateTime('2024-11-30');
        while ($cur <= $end) {
            $dow = (int)$cur->format('N'); // 1=пн, 7=вс
            if ($dow === 3 || $dow === 5) { // ср и пт
                $lessonDates[] = $cur->format('Y-m-d');
            }
            $cur->modify('+1 day');
        }

        foreach ($journalIds as $jInfo) {
            $students = $groupStudents[$jInfo['group']] ?? [];
            foreach ($students as $studentId) {
                foreach ($lessonDates as $idx => $date) {
                    // Случайность: пропуск ~15%, балл ~70%
                    $rand      = ($studentId * 31 + $idx * 17) % 100;
                    $is_absent = $rand < 15;
                    $hasScore  = $rand < 70;
                    $score     = $hasScore ? (($studentId * 7 + $idx * 13) % 8 + 3) : null; // балл 3-10

                    if (!$is_absent && !$hasScore) continue; // пустая ячейка — не сохраняем

                    DB::table('journal_entries')->insertOrIgnore([
                        'journal_id' => $jInfo['id'],
                        'student_id' => $studentId,
                        'date'       => $date,
                        'is_absent'  => $is_absent,
                        'score'      => $score,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Рейтинговые работы для части студентов
                $hasRating = ($studentId % 3) !== 0;
                if ($hasRating) {
                    DB::table('journal_ratings')->insertOrIgnore([
                        'journal_id'   => $jInfo['id'],
                        'student_id'   => $studentId,
                        'rating_score' => ($studentId * 11) % 28 + 3, // 3-30
                        'exam_score'   => null,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Очищаем в обратном порядке зависимостей
        DB::table('journal_ratings')->truncate();
        DB::table('journal_entries')->truncate();
        DB::table('journals')->truncate();
        DB::table('student_profiles')->whereIn(
            'user_id',
            DB::table('users')->where('email', 'like', '%@student.ru')->pluck('id')
        )->delete();
        DB::table('professor_groups')->whereIn(
            'professor_id',
            DB::table('users')->where('email', 'like', '%@university.ru')->pluck('id')
        )->delete();
        DB::table('professor_profiles')->whereIn(
            'user_id',
            DB::table('users')->where('email', 'like', '%@university.ru')->pluck('id')
        )->delete();
        DB::table('users')->where('email', 'like', '%@student.ru')->delete();
        DB::table('users')->where('email', 'like', '%@university.ru')->delete();
        DB::table('subjects')->truncate();
        DB::table('groups')->where('name', 'like', 'ИСП-%')
            ->orWhere('name', 'like', 'ПМ-%')
            ->orWhere('name', 'like', 'ИВТ-%')
            ->orWhere('name', 'like', 'ПИ-%')
            ->orWhere('name', 'like', 'КБ-%')
            ->delete();
    }
};
