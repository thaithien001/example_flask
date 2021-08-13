<?php


namespace Tests\Feature\Api\Participation\GetDatingDays;


use Tests\Feature\Api\Participation\BaseParticipantTest;
use Bachelor\Domain\DatingManagement\ParticipationOpenExpirySetting\Enums\ParticipationOpenExpireStatus;
use Bachelor\Domain\UserManagement\Registration\Enums\RegistrationSteps;
use Bachelor\Domain\UserManagement\User\Enums\UserGender;
use Bachelor\Domain\UserManagement\User\Enums\UserStatus;
use Bachelor\Port\Secondary\Database\MasterDataManagement\Prefecture\ModelDao\Prefecture;
use Bachelor\Port\Secondary\Database\UserManagement\User\ModelDao\User;
use Bachelor\Port\Secondary\Database\UserManagement\User\ModelDao\UserAuth;
use Bachelor\Utility\Helpers\Utility;
use Database\Seeders\CouponSeeder;
use Database\Seeders\ParticipantAwaitingCancelSettingForTestingSeeder;
use Database\Seeders\ParticipantAwaitingCountSettingForTestingSeeder;
use Database\Seeders\ParticipationOpenExpirySettingForTestingNewUserSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;

/**
 * Class ApprovedFemaleCompleted2ndRegistrationTest
 * P_07_04
 * @package Api\Participation
 */
class ApprovedFemaleCompleted2ndRegistrationTest extends BaseParticipantTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->seed(CouponSeeder::class);
        $this->seed(ParticipationOpenExpirySettingForTestingNewUserSeeder::class);
        $this->seed(ParticipantAwaitingCountSettingForTestingSeeder::class);
        $this->seed(ParticipantAwaitingCancelSettingForTestingSeeder::class);

        $this->given_approved_female_logged_in_user();
        $this->monday = Carbon::now()->startOfWeek()->addHours(1);
        $this->given_dating_days();
    }

    private function given_approved_female_logged_in_user()
    {
        $mobileNumber = '0123456';
        $authId = Utility::encode($mobileNumber);

        $prefecture = Prefecture::factory()->create([
            'name' => 'Name',
            'country_id' => 1,
            'status' => 10,
            'admin_id' => 1,
        ]);
        $user = User::factory()->create([
            'name' => 'Test User',
            'gender' => UserGender::Female,
            'mobile_number' => $mobileNumber,
            'status' => UserStatus::ApprovedUser,
            'registration_steps' => RegistrationSteps::StepFinal,
            'prefecture_id' => $prefecture->id,
            'team_member_rate' => 3,
            'flex_point' => 0,
            'is_fake' => 0,
        ]);
        $userAuth = UserAuth::factory()->create([
            'user_id' => $user->id,
            'auth_id' => $authId,
            'auth_type' => 'Mobile'
        ]);
        Passport::actingAs($userAuth);
    }

    public function test_female_opens_app_on_monday()
    {
        // today is Monday
        Carbon::setTestNow($this->monday->copy());
        $response = $this->json('GET', '/api/v2/participation/get-dating-days');
        $this->then_expect_result_on_monday_tuesday_wednesday($response);
    }

    public function test_female_opens_app_on_tuesday()
    {
        // today is Tuesday
        Carbon::setTestNow($this->monday->copy()->addDay(1));
        $response = $this->json('GET', '/api/v2/participation/get-dating-days');
        $this->then_expect_result_on_monday_tuesday_wednesday($response);
    }

    public function test_female_opens_app_on_wednesday()
    {
        // today is Wednesday
        Carbon::setTestNow($this->monday->copy()->addDay(2));
        $response = $this->json('GET', '/api/v2/participation/get-dating-days');
        $this->then_expect_result_on_monday_tuesday_wednesday($response);
    }

    private function then_expect_result_on_monday_tuesday_wednesday(TestResponse $response)
    {
        $response->assertOk();
        $weeks = $response['data']['weeks'];

        //"- I can participate multiple dates of the following days (opened date):
        //+ Saturday, Sunday this week
        //+ Wednesday, Saturday, Sunday next week
        //+ Wednesday next next week
        //- I can NOT participate Wednesday this week (expired); Saturday, Sunday next next week"
        $this->assertEquals(ParticipationOpenExpireStatus::Expired, $weeks[0][0]['open_expiry_status']);
        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[0][1]['open_expiry_status']);
        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[0][2]['open_expiry_status']);

        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[1][0]['open_expiry_status']);
        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[1][1]['open_expiry_status']);
        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[1][2]['open_expiry_status']);

        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[2][0]['open_expiry_status']);
        $this->assertEquals(ParticipationOpenExpireStatus::Closed, $weeks[2][1]['open_expiry_status']);
        $this->assertEquals(ParticipationOpenExpireStatus::Closed, $weeks[2][2]['open_expiry_status']);
    }

    public function test_female_opens_app_on_thursday()
    {
        // today is Thursday
        Carbon::setTestNow($this->monday->copy()->addDay(3));
        $response = $this->json('GET', '/api/v2/participation/get-dating-days');

        $response->assertOk();
        $weeks = $response['data']['weeks'];

        //- I can participate only one of the following days (opened date):
        //+ Sunday this week
        //+ Wednesday, Saturday, Sunday next week
        //+ Wednesday, Saturday next next week
        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[0][2]['open_expiry_status']);

        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[1][0]['open_expiry_status']);
        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[1][1]['open_expiry_status']);
        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[1][2]['open_expiry_status']);

        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[2][0]['open_expiry_status']);
        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[2][1]['open_expiry_status']);

        //Wednesday, Saturday this week will NOT show (expired)
        $this->assertEquals(ParticipationOpenExpireStatus::Expired, $weeks[0][0]['open_expiry_status']);
        $this->assertEquals(ParticipationOpenExpireStatus::Expired, $weeks[0][1]['open_expiry_status']);

        //I can NOT participate on Sunday next next week (closed)
        $this->assertEquals(ParticipationOpenExpireStatus::Closed, $weeks[2][2]['open_expiry_status']);
    }

    public function test_female_opens_app_on_friday_saturday_sunday()
    {
        // today is Friday
        Carbon::setTestNow($this->monday->copy()->addDay(4));
        $response = $this->json('GET', '/api/v2/participation/get-dating-days');
        $this->then_expect_result_friday_saturday_sunday($response);

        // today is Saturday
        Carbon::setTestNow($this->monday->copy()->addDay(5));
        $response = $this->json('GET', '/api/v2/participation/get-dating-days');
        $this->then_expect_result_friday_saturday_sunday($response);

        // today is Sunday
        Carbon::setTestNow($this->monday->copy()->addDay(6));
        $response = $this->json('GET', '/api/v2/participation/get-dating-days');
        $this->then_expect_result_friday_saturday_sunday($response);
    }

    private function then_expect_result_friday_saturday_sunday(TestResponse $response)
    {
        $response->assertOk();
        $weeks = $response['data']['weeks'];

        //- Wednesday, Saturday, Sunday this week will NOT show (expired)
        $this->assertEquals(ParticipationOpenExpireStatus::Expired, $weeks[0][0]['open_expiry_status']);
        $this->assertEquals(ParticipationOpenExpireStatus::Expired, $weeks[0][1]['open_expiry_status']);
        $this->assertEquals(ParticipationOpenExpireStatus::Expired, $weeks[0][2]['open_expiry_status']);

        //- I can participate only one of the following days (opened date):
        //+ Wednesday, Saturday, Sunday next week
        //+ Wednesday, Saturday, Sunday next next week
        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[1][0]['open_expiry_status']);
        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[1][1]['open_expiry_status']);
        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[1][2]['open_expiry_status']);

        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[2][0]['open_expiry_status']);
        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[2][1]['open_expiry_status']);
        $this->assertEquals(ParticipationOpenExpireStatus::Opened, $weeks[2][0]['open_expiry_status']);
    }
}
