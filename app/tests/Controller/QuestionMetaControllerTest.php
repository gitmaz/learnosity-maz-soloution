<?php

namespace App\Tests\Controller;

use App\Entity\QuestionMeta;
use App\Entity\QuestionStat;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class QuestionMetaControllerTest extends WebTestCase
{

    public function testIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        $response = $client->getResponse();
        //$this->assertSame(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(["result", "message"], array_keys($responseData), "\"result\", \"message\" keys are not returned from front controller index");
        $this->assertEquals("success", $responseData["result"], "success result is not returned from front controller index");
        $this->assertEquals("smoke test successful", $responseData["message"], "expected message is not returned from front controller index");
    }


    /**
     * test that we can list an specific activity with breakdown of its students and their time spent on each question)
     *
     */
    public function testCanListActivityDetails()
    {
        $client = static::createClient();

        $em = $client->getContainer()->get('doctrine')
            ->getManager();

        //sample usage of ORM
        //$questionMetaRepo = $em->getRepository(QuestionMeta::class);

        $this->fillDbWithSampleQuestionMetas($em);

        $crawler = $client->request('GET', '/?action=lad&activity_id=1');

        $this->assertResponseIsSuccessful();

        $response = $client->getResponse();
        //$this->assertSame(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        //dd($responseData);

        $this->assertEquals(["1", "2"], array_keys($responseData), "we failed to get a breakdown on students 1 and 2 on this activity");
        $this->assertEquals(["1", "2", "3", "4", "5"], array_keys($responseData["1"]), "we failed to get a breakdown on students 1 to have 5 questions 1 to 5 ");
        $this->assertEquals(null, $responseData["1"]["5"], "we failed to get unfilled question 5 of students 1");
        $this->assertEquals("2", $responseData["2"]["5"], "we failed to get duration 2 on question 5 of students 2");


    }

    private function fillDbWithSampleQuestionMetas($em)
    {

        $questionMetaInsertsSql = file_get_contents(__DIR__ . "/../../sqls/question_meta.sql");
        $database = $em->getConnection();

        //first truncate table to avoid duplicate ids exception:
        $stmt = $database->prepare("truncate table question_meta");
        $stmt->execute();

        $stmt = $database->prepare($questionMetaInsertsSql);
        $res = $stmt->execute();
        return $res;
    }

    private function fillDbWithSampleQuestionStats($em)
    {

        $questionStatInsertsSql = file_get_contents(__DIR__ . "/../../sqls/question_stat.sql");
        $database = $em->getConnection();

        //first truncate table to avoid duplicate ids exception:
        $stmt = $database->prepare("truncate table question_stat");
        $stmt->execute();

        $stmt = $database->prepare($questionStatInsertsSql);
        $res = $stmt->execute();
        return $res;
    }

    /**
     * test that we can find longest activity amongst all students in average
     *
     */
    public function testCanFindLongestActivity()
    {
        $client = static::createClient();

        $em = $client->getContainer()->get('doctrine')
            ->getManager();

        $this->fillDbWithSampleQuestionStats($em);

        $crawler = $client->request('GET', '/?action=fla');

        $this->assertResponseIsSuccessful();

        $response = $client->getResponse();
        //$this->assertSame(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(["activity_id", "average_time_spent"], array_keys($responseData), "we failed to get keys activity_id and average_time_spent for fla endpoint ");
        $this->assertEquals(["1", 10], array_values($responseData), "we failed differentiate activity 1 as the longest with average of 8 minutes ");

    }

    /**
     * test that we can "receive" question meta through api endpoint and save it in db with correct duration calculations for single question
     *
     */
    public function testCanUpdateMatchingQuestionsMeta()
    {
        $client = static::createClient();

        $em = $client->getContainer()->get('doctrine')
            ->getManager();

        // usage of ORM to remove question with activity id = 3 user id = 1 and question nr = 1 if exists to prevent exception
        $questionMetaRepo = $em->getRepository(QuestionMeta::class);

        $question311 = $questionMetaRepo->findBy(['activityId' => 3, 'userId' => 1, 'questionNr' => 1]);
        if ($question311 != null) {
            $em->remove($question311);
        }
        $question312 = $questionMetaRepo->findBy(['activityId' => 3, 'userId' => 1, 'questionNr' => 2]);
        if ($question311 != null) {
            $em->remove($question312);
        }

        //also remove the these two quesions header record if they exist
        $questionStatRepo = $em->getRepository(QuestionStat::class);
        $question3Stat31 = $questionStatRepo->findBy(['activityId' => 3, 'userId' => 1]);
        if ($question3Stat31 != null) {
            $em->remove($question3Stat31);
        }
        //apply to db at once
        $em->flush();

        //now try to create two questions through endpoint and see if they are being created
        $crawler = $client->request('GET', '/?action=receive&activity_id=3&user_id=1&timestamp=1&name=start');

        $this->assertResponseIsSuccessful();

        $response = $client->getResponse();
        //$this->assertSame(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(["result"], array_keys($responseData), "we failed to get key result for receive endpoint on start event ");
        $this->assertEquals("success", $responseData["result"], "we failed to get success on start event  ");


        $crawler = $client->request('GET', '/?action=receive&activity_id=3&user_id=1&timestamp=5&name=next');

        $this->assertResponseIsSuccessful();

        $response = $client->getResponse();
        //$this->assertSame(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(["result"], array_keys($responseData), "we failed to get key result for receive endpoint on next event ");
        $this->assertEquals("success", $responseData["result"], "we failed to get success on next event ");


        $crawler = $client->request('GET', '/?action=receive&activity_id=3&user_id=1&timestamp=7&name=stop');

        $this->assertResponseIsSuccessful();

        $response = $client->getResponse();
        //$this->assertSame(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(["result"], array_keys($responseData), "we failed to get key result for receive endpoint on stop event ");
        $this->assertEquals("success", $responseData["result"], "we failed the get success on end event ");

    }

    /**
     * test that we can "receive" many question metas for many activities(events) through a post api endpoint and save them
     * in db with correct duration calculations for each question
     *
     */
    public function testCanReceivePayloadAsOfManyEventsViaPost()
    {
        $client = static::createClient();

        $em = $client->getContainer()->get('doctrine')
            ->getManager();

        //reset initial records to avoid exception on repeated ids if the test runs twice
        $this->fillDbWithSampleQuestionMetas($em);
        $this->fillDbWithSampleQuestionStats($em);

        $crawler = $client->request('POST', '/?action=receive', [
            'events' => [
                [
                    "name" => "start", // "start" | "next" | "stop"
                    "timestamp" => 1545188359, // Unix timestamp of the event
                    "user_id" => "user134", // User who triggered the event
                    "activity_id" => "Math-1" // Activity that triggered the event
                ]
            ],
        ]);

        $questionMetaRepo = $em->getRepository(QuestionMeta::class);
        $questionMath_1_user134_1 = $questionMetaRepo->findOneBy(['activityId' => 'Math-1', 'userId' => 'user134', 'questionNr' => 1]);
        $questionMath_1_user134_1Values = [$questionMath_1_user134_1->getOpenedAt(), $questionMath_1_user134_1->getClosedAt(), $questionMath_1_user134_1->getQuestionNr()];

        $this->assertEquals($questionMath_1_user134_1Values, [1545188359, null, 1], "timestamps or number of newly payloaded question is not correct");

        $questionStatRepo = $em->getRepository(QuestionStat::class);
        $questionMath_1_user134stat = $questionStatRepo->findOneBy(['activityId' => 'Math-1', 'userId' => 'user134']);
        $questionMath_1_user134statValues = [
            $questionMath_1_user134stat->getStartedAtTimestamp(),
            $questionMath_1_user134stat->getStoppedAtTimestamp(),
            $questionMath_1_user134stat->getTotDurations(),
            $questionMath_1_user134stat->getStatus()];

        $this->assertEquals($questionMath_1_user134statValues, [1545188359, null, 0, 'started'], "details for stat record of newly payloaded question is not correct");

    }

    /**
     * test that we can "recieveing" a delayed question applies correction by inserting new question and relocating previous closedAt
     *
     */
    public function testCanSettleRacingConditionOnDelayedPackets()
    {
        $client = static::createClient();

        $em = $client->getContainer()->get('doctrine')
            ->getManager();

        //focus on a question we want to handle an overlapping timestamp ( a timestamp that arrives after a question closure
        // but is between the question open and close times). The question should be corrected by having its closed_at replaced
        // with this timestamp and this closed_at, should mark the end of a new question with its start set as this arrived timestamp)


        // usage of ORM to remove question with activity id = 3 user id = 1 and question nr = 1 if exists to prevent exception
        $questionMetaRepo = $em->getRepository(QuestionMeta::class);

        $question124 = $questionMetaRepo->findOneBy(['activityId' => 1, 'userId' => 2, 'questionNr' => 4]);

        //if test case records do not exist or $question124 with corrected value of 8 exists (from last run of this test case) reset initial test records
        if ($question124 == null || $question124->getClosedAt() == 8) {
            $this->fillDbWithSampleQuestionMetas($em);
            $question124 = $questionMetaRepo->findOneBy(['activityId' => 1, 'userId' => 2, 'questionNr' => 4]);
        }
        $question124OpenedAt = $question124->getOpenedAt();
        $question124ClosedAt = $question124->getClosedAt();
        $timestampCandidate = ($question124ClosedAt + $question124OpenedAt) / 2;

        //now try to mock a delayed timestamp arriving after $question124ClosedAt is used wrongly as closed time of the question
        // as that one is arrived too late
        $crawler = $client->request('GET', "/?action=receive&activity_id=1&user_id=2&timestamp=$timestampCandidate&name=next");

        $this->assertResponseIsSuccessful();

        $response = $client->getResponse();
        //$this->assertSame(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(["result"], array_keys($responseData), "we failed to get key result for receive endpoint on start event ");
        $this->assertEquals("success", $responseData["result"], "we failed to get success on start event  ");

        //check if our registering of the delayed timestamp has caused the closed at timestamp of the overlapping question to be moved to the arrived
        // timestamp
        $question124New = $questionMetaRepo->findOneBy(['activityId' => 1, 'userId' => 2, 'questionNr' => 4]);
        $question124NewClosedAt = $question124New->getClosedAt();

        $this->assertEquals(8, $question124NewClosedAt, "closed at timestamp of overlapping question is not corrected  ");
        //check if our registering of the delayed timestamp has caused a new question to be inserted after overlapping question with overapping
        // question old closedAt moved closedAtt of this question and openedAt as newly arrived timestamp

        $question125 = $questionMetaRepo->findOneBy(['activityId' => 1, 'userId' => 2, 'questionNr' => 5]);
        $question125Values = [$question125->getOpenedAt(), $question125->getClosedAt(), $question125->getQuestionNr()];

        $this->assertEquals($question125Values, [8, 9, 5], "timestamps of newly inserted (due to overlap) question is not correct");

    }

}
