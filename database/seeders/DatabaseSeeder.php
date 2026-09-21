<?php

namespace Database\Seeders;

use App\Models\BodyMeasurement;
use App\Models\CheckIn;
use App\Models\Exercise;
use App\Models\GymClass;
use App\Models\GymClassSchedule;
use App\Models\MembershipPackage;
use App\Models\MemberSubscription;
use App\Models\Notification;
use App\Models\PTBooking;
use App\Models\TrainerAvailability;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo Users
        $admin = User::create([
            'name' => 'Quản Trị Viên Gym 88',
            'email' => 'admin@gym88.vn',
            'phone' => '0901234567',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'address' => '88 Phố Huế, Hai Bà Trưng, Hà Nội',
            'is_active' => true,
        ]);

        $staff = User::create([
            'name' => 'Nguyễn Thị Lễ Tân',
            'email' => 'staff@gym88.vn',
            'phone' => '0909887766',
            'password' => Hash::make('password123'),
            'role' => 'staff',
            'gender' => 'female',
            'date_of_birth' => '1998-04-10',
            'address' => 'Hà Nội',
            'is_active' => true,
        ]);

        $trainer1 = User::create([
            'name' => 'Nguyễn Văn Hùng (Coach Hùng)',
            'email' => 'trainer1@gym88.vn',
            'phone' => '0911223344',
            'password' => Hash::make('password123'),
            'role' => 'trainer',
            'gender' => 'male',
            'date_of_birth' => '1993-05-15',
            'address' => 'Hà Nội',
            'is_active' => true,
        ]);

        $trainer2 = User::create([
            'name' => 'Trần Thị Mai (Coach Mai Yoga)',
            'email' => 'trainer2@gym88.vn',
            'phone' => '0922334455',
            'password' => Hash::make('password123'),
            'role' => 'trainer',
            'gender' => 'female',
            'date_of_birth' => '1995-08-20',
            'address' => 'Hà Nội',
            'is_active' => true,
        ]);

        $member1 = User::create([
            'name' => 'Lê Hoàng Nam',
            'email' => 'member1@gym88.vn',
            'phone' => '0933445566',
            'password' => Hash::make('password123'),
            'role' => 'member',
            'gender' => 'male',
            'date_of_birth' => '1998-03-12',
            'address' => 'Cầu Giấy, Hà Nội',
            'emergency_contact' => '0988776655 (Mẹ)',
            'is_active' => true,
        ]);

        $member2 = User::create([
            'name' => 'Phạm Thu Hà',
            'email' => 'member2@gym88.vn',
            'phone' => '0944556677',
            'password' => Hash::make('password123'),
            'role' => 'member',
            'gender' => 'female',
            'date_of_birth' => '2000-11-25',
            'address' => 'Đống Đa, Hà Nội',
            'is_active' => true,
        ]);

        // 2. Lịch rảnh Huấn luyện viên (Trainer Availabilities: 1=Mon, 2=Tue, ..., 6=Sat)
        foreach ([$trainer1, $trainer2] as $trainer) {
            for ($day = 1; $day <= 6; $day++) {
                TrainerAvailability::create([
                    'trainer_id' => $trainer->id,
                    'day_of_week' => $day,
                    'start_time' => '08:00:00',
                    'end_time' => '12:00:00',
                    'is_active' => true,
                ]);
                TrainerAvailability::create([
                    'trainer_id' => $trainer->id,
                    'day_of_week' => $day,
                    'start_time' => '14:00:00',
                    'end_time' => '20:00:00',
                    'is_active' => true,
                ]);
            }
        }

        // 3. Gói tập (Membership Packages)
        $silver = MembershipPackage::create([
            'name' => 'Gói 1 Tháng Cơ Bản (Silver)',
            'slug' => 'silver-1-month',
            'description' => 'Tự do tập luyện tại tất cả các khu vực tạ, cardio không giới hạn giờ tập.',
            'price' => 600000,
            'duration_days' => 30,
            'has_pt' => false,
            'pt_sessions' => 0,
            'benefits' => [
                'Full access toàn bộ máy tập gym & cardio',
                'Tủ đồ locker thông minh miễn phí',
                'Phòng tắm nóng lạnh, nước uống lọc',
            ],
            'is_active' => true,
        ]);

        $gold = MembershipPackage::create([
            'name' => 'Gói 3 Tháng Nâng Cao (Gold)',
            'slug' => 'gold-3-months',
            'description' => 'Gói tập được yêu thích nhất, tặng kèm 3 buổi huấn luyện cá nhân 1-1 chuyên sâu.',
            'price' => 1600000,
            'duration_days' => 90,
            'has_pt' => true,
            'pt_sessions' => 3,
            'benefits' => [
                'Toàn bộ quyền lợi gói Silver',
                '03 buổi tập cùng Huấn luyện viên cá nhân (PT 1:1)',
                'Đo chỉ số InBody định kỳ hàng tháng',
                'Tham gia miễn phí các lớp Yoga, Zumba cơ bản',
            ],
            'is_active' => true,
        ]);

        $vip = MembershipPackage::create([
            'name' => 'Gói 1 Năm VIP Diamond',
            'slug' => 'vip-12-months',
            'description' => 'Đẳng cấp hội viên cao cấp nhất, trọn gói phòng xông hơi khô/ướt và 12 buổi PT.',
            'price' => 5500000,
            'duration_days' => 365,
            'has_pt' => true,
            'pt_sessions' => 12,
            'benefits' => [
                'Toàn bộ quyền lợi gói Gold',
                '12 buổi tập cùng Huấn luyện viên cá nhân (PT 1:1)',
                'Sử dụng phòng xông hơi đá muối Himalaya & bể sục Jacuzzi',
                'Khăn tập cao cấp miễn phí mỗi buổi',
                'Bảo lưu tối đa 60 ngày miễn phí',
            ],
            'is_active' => true,
        ]);

        // 4. Kích hoạt Subscription mẫu cho Member 1
        $sub1 = MemberSubscription::create([
            'user_id' => $member1->id,
            'package_id' => $vip->id,
            'membership_code' => 'GYM-VIP-88001',
            'qr_token' => 'DYN-'.hash('sha256', $member1->id.'-GYM-VIP-88001-'.time()),
            'start_date' => Carbon::now()->subDays(15)->toDateString(),
            'end_date' => Carbon::now()->addDays(350)->toDateString(),
            'price_paid' => 5500000,
            'status' => 'active',
            'remaining_pt_sessions' => 10,
        ]);

        Transaction::create([
            'user_id' => $member1->id,
            'transaction_code' => 'TXN-20260901-00881',
            'reference_id' => $sub1->id,
            'reference_type' => 'subscription',
            'amount' => 5500000,
            'currency' => 'VND',
            'payment_method' => 'vietqr',
            'status' => 'success',
            'transaction_date' => Carbon::now()->subDays(15),
            'notes' => 'Thanh toán thành công qua chuyển khoản VietQR',
        ]);

        // 5. Thư viện bài tập (Exercises)
        $exercisesData = [
            [
                'name' => 'Barbell Bench Press (Đẩy ngực ngang)',
                'muscle_group' => 'Ngực',
                'difficulty' => 'intermediate',
                'equipment' => 'Thanh đòn Barbell, Ghế phẳng',
                'instructions' => 'Nằm ngửa trên ghế, siết bả vai, đẩy tạ lên vuông góc với thân người rồi hạ chậm có kiểm soát về giữa ngực.',
                'calories_burn_per_minute' => 7.5,
            ],
            [
                'name' => 'Incline Dumbbell Press (Đẩy ngực trên)',
                'muscle_group' => 'Ngực',
                'difficulty' => 'beginner',
                'equipment' => 'Tạ đơn Dumbbells, Ghế dốc 30-45 độ',
                'instructions' => 'Chỉnh ghế nghiêng, giữ tạ ngang vai, đẩy tạ lên cao hít thở đều.',
                'calories_burn_per_minute' => 6.8,
            ],
            [
                'name' => 'Lat Pulldown (Kéo xô máy cáp)',
                'muscle_group' => 'Lưng - Xô',
                'difficulty' => 'beginner',
                'equipment' => 'Máy cáp kéo xô',
                'instructions' => 'Cầm rộng tay, giữ thẳng lưng ngực ưỡn nhẹ, kéo thanh đòn về phía xương quai xanh.',
                'calories_burn_per_minute' => 6.5,
            ],
            [
                'name' => 'Barbell Deadlift',
                'muscle_group' => 'Lưng - Đùi sau',
                'difficulty' => 'advanced',
                'equipment' => 'Thanh đòn Barbell, Tạ bánh Olympic',
                'instructions' => 'Đứng mở chân bằng vai, lưng giữ thẳng tự nhiên, siết cơ bụng và cơ mông kéo tạ thẳng đứng.',
                'calories_burn_per_minute' => 10.2,
            ],
            [
                'name' => 'Barbell Back Squat (Gánh đùi sau)',
                'muscle_group' => 'Chân - Đùi',
                'difficulty' => 'intermediate',
                'equipment' => 'Squat Rack, Thanh đòn',
                'instructions' => 'Đặt thanh đòn trên cầu vai, hạ hông sâu xuống qua đầu gối, gót chân trụ vững.',
                'calories_burn_per_minute' => 9.5,
            ],
            [
                'name' => 'Standing Dumbbell Lateral Raise (Bay vai)',
                'muscle_group' => 'Vai',
                'difficulty' => 'beginner',
                'equipment' => 'Tạ đơn',
                'instructions' => 'Đứng thẳng, nâng hai cánh tay sang ngang ngang tầm vai, kiểm soát khi hạ xuống.',
                'calories_burn_per_minute' => 5.5,
            ],
            [
                'name' => 'Hanging Leg Raise (Đu xà gập bụng)',
                'muscle_group' => 'Cơ bụng',
                'difficulty' => 'intermediate',
                'equipment' => 'Xà đơn',
                'instructions' => 'Treo người trên xà, siết cơ bụng nâng hai chân lên cao tối thiểu 90 độ.',
                'calories_burn_per_minute' => 6.0,
            ],
        ];

        foreach ($exercisesData as $item) {
            Exercise::create(array_merge($item, ['is_active' => true]));
        }

        // 6. Lớp học nhóm (Gym Classes & Schedules)
        $combatClass = GymClass::create([
            'name' => 'Body Combat Đốt Mỡ Cao Độ',
            'description' => 'Lớp tập võ thuật trên nền nhạc sôi động giúp giải tỏa căng thẳng và tiêu hao tới 700 kcal.',
            'default_duration_minutes' => 60,
            'is_active' => true,
        ]);

        $yogaClass = GymClass::create([
            'name' => 'Vinyasa Flow Yoga',
            'description' => 'Lớp học kết nối hơi thở và chuyển động mềm mại, nâng cao độ dẻo dai và định hình vóc dáng.',
            'default_duration_minutes' => 60,
            'is_active' => true,
        ]);

        // Tạo lịch học trong tuần
        GymClassSchedule::create([
            'gym_class_id' => $combatClass->id,
            'trainer_id' => $trainer1->id,
            'room' => 'Studio 1 (Tầng 3)',
            'start_time' => Carbon::tomorrow()->setTime(18, 30),
            'end_time' => Carbon::tomorrow()->setTime(19, 30),
            'max_capacity' => 25,
            'current_bookings_count' => 0,
            'status' => 'scheduled',
        ]);

        GymClassSchedule::create([
            'gym_class_id' => $yogaClass->id,
            'trainer_id' => $trainer2->id,
            'room' => 'Zen Studio (Tầng 4)',
            'start_time' => Carbon::tomorrow()->addDay()->setTime(7, 0),
            'end_time' => Carbon::tomorrow()->addDay()->setTime(8, 0),
            'max_capacity' => 20,
            'current_bookings_count' => 0,
            'status' => 'scheduled',
        ]);

        // 7. Lịch hẹn PT sắp tới
        PTBooking::create([
            'user_id' => $member1->id,
            'trainer_id' => $trainer1->id,
            'subscription_id' => $sub1->id,
            'start_time' => Carbon::tomorrow()->setTime(9, 0),
            'end_time' => Carbon::tomorrow()->setTime(10, 0),
            'status' => 'confirmed',
            'notes' => 'Buổi tập kỹ thuật Squat & Deadlift an toàn',
        ]);

        // 8. Dữ liệu InBody theo dõi tiến trình
        BodyMeasurement::create([
            'user_id' => $member1->id,
            'measured_at' => Carbon::now()->subMonths(2)->toDateString(),
            'weight_kg' => 78.5,
            'height_cm' => 175.0,
            'bmi' => 25.63,
            'body_fat_percentage' => 22.5,
            'muscle_mass_kg' => 33.0,
            'chest_cm' => 98.0,
            'waist_cm' => 86.0,
            'hips_cm' => 99.0,
            'notes' => 'Lần đo đầu tiên khi bắt đầu tham gia gym',
        ]);

        BodyMeasurement::create([
            'user_id' => $member1->id,
            'measured_at' => Carbon::now()->subMonths(1)->toDateString(),
            'weight_kg' => 76.0,
            'height_cm' => 175.0,
            'bmi' => 24.82,
            'body_fat_percentage' => 20.2,
            'muscle_mass_kg' => 33.8,
            'chest_cm' => 99.0,
            'waist_cm' => 83.5,
            'hips_cm' => 98.0,
            'notes' => 'Giảm 2.5kg mỡ, tăng cơ nạc',
        ]);

        BodyMeasurement::create([
            'user_id' => $member1->id,
            'measured_at' => Carbon::now()->toDateString(),
            'weight_kg' => 74.2,
            'height_cm' => 175.0,
            'bmi' => 24.23,
            'body_fat_percentage' => 17.8,
            'muscle_mass_kg' => 34.5,
            'chest_cm' => 101.0,
            'waist_cm' => 80.0,
            'hips_cm' => 97.0,
            'notes' => 'Tiến độ cực kỳ ấn tượng sau 2 tháng',
        ]);

        // 9. Lịch sử Check-In gần đây
        for ($i = 5; $i >= 1; $i--) {
            CheckIn::create([
                'user_id' => $member1->id,
                'subscription_id' => $sub1->id,
                'check_in_time' => Carbon::now()->subDays($i)->setTime(17, 45),
                'check_out_time' => Carbon::now()->subDays($i)->setTime(19, 15),
                'method' => 'dynamic_qr',
                'notes' => 'Quét cổng tự động Smart Turnstile',
            ]);
        }

        // 10. Thông báo mẫu
        Notification::create([
            'user_id' => $member1->id,
            'channel' => 'in_app',
            'type' => 'welcome',
            'title' => 'Chào mừng bạn đến với Gym 88!',
            'message' => 'Bạn đã kích hoạt thành công Gói 1 Năm VIP Diamond. Chúc bạn có hành trình tập luyện tuyệt vời.',
            'is_read' => true,
            'read_at' => Carbon::now()->subDays(15),
        ]);

        Notification::create([
            'user_id' => $member1->id,
            'channel' => 'in_app',
            'type' => 'pt_reminder',
            'title' => 'Nhắc nhở: Lịch tập cùng PT ngày mai',
            'message' => 'Bạn có lịch tập cùng HLV Nguyễn Văn Hùng lúc 09:00 ngày mai. Hãy đến trước 10 phút để khởi động nhé!',
            'is_read' => false,
        ]);
    }
}
