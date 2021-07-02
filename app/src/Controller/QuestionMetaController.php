<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/*
 * This class is responsible for exposing endpoint required for populating question meta data (timestamps) as well as
 *  retrieving breakdowns of these timing information based on activity and users, it also calculates the longest activity
 *
 * Note: api formed on GET payloads for testability from browser address bar but Posts payloads are also applicable via reciece
 *  by supplying a post payload such as {events : [array of events key values]};
 *
 * IMPORTANT: at the moment annotation routes does not work, use / route with query string containing "action=" instead
 *  where action is set equal to one of the fallowing values: "lad" (list activity details), "fla" (find longest activity) ,
 *  "receive" (for receiving new  question meta and populationg the database) or "all" (for dump visualization of an activity)
 */

class QuestionMetaController extends AbstractController
{

    //todo: move these variables to the return object of the functions that fill it for better testability,
    // left as is, for better readability
    private $lastClosedQuestionNr = null;
    private $lastClosedQuestionDuration = null;

    //Note: In current current configuration this route uses base route instead (/) and acts as front controller
    // use http://localhost/?action=x  where x is one of the { lad, fla, receive , all , help } keywords
    /*
     * @Route("/qm/home", name="question_meta_home")
     */
    public function index(Request $request): Response
    {
        $userId = $request->get("user_id");
        $activityId = $request->get("activity_id");
        $action = $request->get("action");
        $payload = $request->get("events");

        switch ($action) {
            //example usage: http://localhost/?action=help
            case 'help': // prints out examples of how to form these endpoints
                return $this->showUsage($request);
                break;

            //example usage: http://localhost/?action=receive&activity_id=1&user_id=1&timestamp=1&name=start
            //              http://localhost/?action=receive&activity_id=1&user_id=1&timestamp=3&name=next;
            //              http://localhost/?action=receive&activity_id=1&user_id=5&timestamp=5&name=stop;
            case 'receive'://populate user question times on any activity
                if ($payload != null) {
                    return $this->receivePayload($payload);
                } else {
                    return $this->updateMatchingQuestionsMeta($request);
                }
                break;

            //example usage: http://localhost/?action=lad&activity_id=1
            case 'lad'://list an activity break down on its students and time each student spent on each question
                return $this->listActivityDetails($request);
                break;

            //example usage: http://localhost/?action=fla
            case 'fla'://find longest time students spent on an activity among its activity id
                return $this->findLongestActivity($request);
                break;

            //example usage: http://localhost/?action=dump&activity_id=1&user_id=1
            case 'dump': //list details of all questions of specific student on specific activities
                // (this is for my own visual debugging purpose on browser)
                $em = $this->getDoctrine()->getManager();
                $database = $em->getConnection();

                $sql = "select *  from question_meta where user_id = :user_id and activity_id = :activity_id";

                $res = $database->fetchAll($sql, ['user_id' => $userId, 'activity_id' => $activityId]);
                dd($res);
                break;
        }

        return new JsonResponse(["result" => "success", "message" => "smoke test successful"]);
    }

    /**
     * @Route("/qm/lad", name="question_meta_list_activity_details")
     */
    public function listActivityDetails(Request $request): Response
    {
        $sql = "select *  from question_meta where activity_id = :activity_id order by user_id, question_nr";

        $activityId = $request->get("activity_id");

        $em = $this->getDoctrine()->getManager();
        $database = $em->getConnection();

        $res = $database->fetchAll($sql, ['activity_id' => $activityId]);


        $formattedResult = [];
        foreach ($res as $item) {
            $formattedResult[$item["user_id"]][$item["question_nr"]] = $item["duration"];
        }
        return new JsonResponse($formattedResult);

    }

    /**
     * @Route("/qm/fla", name="question_meta_find_longest_activity")
     */
    public function findLongestActivity(Request $request): Response
    {
        $sql = "select averages.* from (select activity_id, avg(tot_durations) as average_time_spent from question_stat where tot_durations!=0 and status='stopped' group by activity_id) averages order by average_time_spent desc limit 1";

        $em = $this->getDoctrine()->getManager();
        $database = $em->getConnection();

        $res = $database->fetchAssoc($sql, []);

        return new JsonResponse($res);
    }

    /**
     * @Route("/qm/receive", name="question_receive_payload")
     */
    public function receivePayload($payload)
    {
        $results=[];
        foreach ($payload as $event) {
            $result=$this->updateMatchingQuestionsMeta($event);
            $results[]=$result->getContent();
        }

        return new JsonResponse($results);
    }

    /**
     * @Route("/qm/umq", name="question_meta_update_matching_questions")
     */
    public function updateMatchingQuestionsMeta($request): Response
    {

        $em = $this->getDoctrine()->getManager();
        $database = $em->getConnection();

        if ($request instanceof Request) {
            $eventType = $request->get("name");
        } else {
            $eventType = $request["name"];
        }


        //Abnormal case : (timestamp matches a question with already assigned Closed_at)
        // reason: wrong question is closed because of a race condition (close ticket of newer question arriving
        // earlier because of an intermittent slow internet connection)
        if (!$this->applyOverlapConditionCorrections($request, $database)) {

            //check if timestamp is suitable for closing a non closed question
            //Normal case (timestamp matches a question with empty Closed_at)
            if ($eventType == "next" || $eventType == "stop") {

                //close any unclosed question as this timestamp marks closing of such
                if ($this->closeClosestUnclosedQuestion($request, $database)) {

                    if ($eventType == "stop") {
                        $this->stopActivityStatisticsRecord($request, $database, $this->lastClosedQuestionDuration);
                    } else {
                        $this->updateActivityStatisticsRecord($request, $database, $this->lastClosedQuestionDuration);
                    }
                    //an open question existed and was closed

                } else {
                    return new JsonResponse(["result" => "failure"]);
                    //no open question existed

                }
            }

            if ($eventType == "start" || $eventType == "next") {

                //insert a new question as this timestamp marks opening of such
                $previousQuestionNr = $this->lastClosedQuestionNr;
                if ($this->openNewQuestion($request, $database, $previousQuestionNr)) {
                    //return true;
                    if ($eventType == "start") {
                        $this->insertActivityStatisticsRecord($request, $database);
                    }
                    return new JsonResponse(["result" => "success"]);
                } else {
                    //return false;
                    return new JsonResponse(["result" => "failure"]);
                }
            }

        }

        return new JsonResponse(["result" => "success"]);

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function showUsage(Request $request)
    {
        return new JsonResponse([
            "example usages" => [
                "Receive A New Question Meta" => "http://localhost/?action=receive&activity_id=1&user_id=1&timestamp=1",
                "List Activity Details" => "http://localhost/?action=lad&activity_id=1",
                "Find Longest Activity" => "http://localhost/?action=fla",
                "Dump All" => "http://localhost/?action=dump&activity_id=1&user_id=1",
            ]
        ]);
    }


    /**
     * @param $request
     * @param $database doctrine dbal connection
     * @return bool
     */
    private function closeClosestUnclosedQuestion($request, $database): bool
    {
        //find closest unclosed question (to timestamp) to be closed later below
        $sql = "select id, opened_at, closed_at, question_nr from question_meta where user_id = :user_id and activity_id = :activity_id and closed_at is null and opened_at<:timestamp  order by opened_at desc limit 1";

        if ($request instanceof Request) {
            $userId = $request->get("user_id");
            $activityId = $request->get("activity_id");
            $timestamp = $request->get("timestamp");
        }else{
            $userId = $request["user_id"];
            $activityId = $request["activity_id"];
            $timestamp = $request["timestamp"];
        }

        $res = $database->fetchAssoc($sql, ['user_id' => $userId, 'activity_id' => $activityId, 'timestamp' => $timestamp]);

        if (isset($res["id"])) {
            $idToClose = $res["id"];
            $openedAt = $res["opened_at"];
            $this->lastClosedQuestionNr = $res["question_nr"];

            $closedAt = $timestamp;
            $this->lastClosedQuestionDuration = $closedAt - $openedAt;

            //close the question
            $closeSql = "update question_meta set closed_at=:closedAt, duration=closed_at-opened_at where id= :idToClose";

            $stmt = $database->prepare($closeSql);
            $stmt->bindValue('closedAt', $closedAt);
            $stmt->bindValue('idToClose', $idToClose);
            $res = $stmt->execute();

            //unclosed question found and closed
            return $res;
        } else {

            //no unclosed question found
            return false;
        }
    }

    /**
     * @param $request
     * @param $database doctrine dbal connection
     * @param $previousQuestionNr precceding question number
     * @return bool
     */
    private function openNewQuestion($request, $database, $previousQuestionNr): bool
    {

        if ($request instanceof Request) {
            $userId = $request->get("user_id");
            $activityId = $request->get("activity_id");
            $timestamp = $request->get("timestamp");
        }else{
            $userId = $request["user_id"];
            $activityId = $request["activity_id"];
            $timestamp = $request["timestamp"];
        }

        if ($previousQuestionNr == null) {
            $previousQuestionNr = 0;
        }
        $thisQuestionNr = $previousQuestionNr + 1;
        $restorationSql = "insert into question_meta (activity_id, user_id, opened_at, question_nr) values (:activityId, :userId, :openedAt, :questionNr)";

        $stmt = $database->prepare($restorationSql);
        $stmt->bindValue('activityId', $activityId);
        $stmt->bindValue('userId', $userId);
        $stmt->bindValue('openedAt', $timestamp);
        $stmt->bindValue('questionNr', $thisQuestionNr);

        $res = $stmt->execute();

        return $res;
    }

    /**
     * @param $request
     * @param $database doctrine dbal connection
     * @param $questionNrAfter question number from which the increment should happen (excluding itself)
     * @return bool
     */
    private function incrementQuestionNumbers($request, $database, $questionNrAfter): bool
    {

        if ($request instanceof Request) {
            $userId = $request->get("user_id");
            $activityId = $request->get("activity_id");
        }else{
            $userId = $request["user_id"];
            $activityId = $request["activity_id"];
        }

        $incrementSql = "update question_meta set question_nr=question_nr+1 where user_id = :userId and activity_id = :activityId and question_nr > :questionNrAfter";

        $stmt = $database->prepare($incrementSql);
        $stmt->bindValue('activityId', $activityId);
        $stmt->bindValue('userId', $userId);
        $stmt->bindValue('questionNrAfter', $questionNrAfter);

        $res = $stmt->execute();

        return $res;
    }

    /**
     * @param $request
     * @param $database doctrine dbal connection
     * @return bool
     */
    private function applyOverlapConditionCorrections($request, $database): bool
    {

        if ($request instanceof Request) {
            $userId = $request->get("user_id");
            $activityId = $request->get("activity_id");
            $timestamp = $request->get("timestamp");
            $eventName = $request->get("name");
        } else {
            $userId = $request["user_id"];
            $activityId = $request["activity_id"];
            $timestamp = $request["timestamp"];
            $eventName = $request["name"];
        }


        if ($eventName == "start" || $eventName == "stop") {
            return false;//no correction needed
        }

        //first find the matching question that can have this timestamp as its correct closed_at value
        $sql = "select id, closed_at, question_nr from question_meta where user_id = :userId and activity_id=:activityId and opened_at < :timestamp and :timestamp < closed_at";
        $res = $database->fetchAssoc($sql, ['userId' => $userId, 'activityId' => $activityId, 'timestamp' => $timestamp]);

        if (isset($res["id"])) {
            $idToCorrect = $res["id"];
            $newClosedAt = $timestamp;
            $wrongClosedAt = $res["closed_at"];
            $overlappingQuestionNr = $res["question_nr"];
            //correction involves two steps
            //first : accept the timestamp as correct closed_at value for the matching question
            //second: insert a new question with its closed_at as above old closed_at and its opened_at as above new closed_at
            // (this is for restoring the question which is missed out due to network problems)

            //1. correct the matching record close_at to be equal to timestamp
            //dd($res["id"]);
            $correctionSql = "update question_meta set closed_at=:closedAt, duration=closed_at-opened_at where id= :idToClose";

            $stmt = $database->prepare($correctionSql);
            $stmt->bindValue('closedAt', $newClosedAt);
            $stmt->bindValue('idToClose', $idToCorrect);
            $res = $stmt->execute();

            //2. insert the restored question with its opened_at as matching question new closed_at and its closed_at as the matching question old closed_at
            $restoredQuestionNr = $overlappingQuestionNr + 1;

            //first open up space for new isertion.increment all questions which already were existing after the new question we are going to insert as their numbers need corrections
            $this->incrementQuestionNumbers($request, $database, $overlappingQuestionNr);

            $restorationSql = "insert into question_meta (activity_id, user_id, opened_at, closed_at, question_nr, duration) values (:activityId, :userId, :openedAt, :closedAt, :questionNr, :closedAt - :openedAt)";

            $stmt = $database->prepare($restorationSql);
            $stmt->bindValue('activityId', $activityId);
            $stmt->bindValue('userId', $userId);
            $stmt->bindValue('openedAt', $timestamp);
            $stmt->bindValue('closedAt', $wrongClosedAt);
            $stmt->bindValue('questionNr', $restoredQuestionNr);

            $res = $stmt->execute();

            //correction applied
            return true;
        } else {
            //no correction needed
            return false;
        }

    }

    /**
     * @param $request
     * @param $database  doctrine dbal connection
     * @return mixed
     */
    private function insertActivityStatisticsRecord($request, $database)
    {
        if ($request instanceof Request) {
            $userId = $request->get("user_id");
            $activityId = $request->get("activity_id");
            $startedAt = $request->get("timestamp");
        }else{
            $userId = $request["user_id"];
            $activityId = $request["activity_id"];
            $startedAt = $request["timestamp"];
        }

        $createStatRecordSql = "insert into question_stat (activity_id, user_id, latest_question_nr, tot_durations, status, started_at_timestamp) values (:activityId, :userId, 1, 0, 'started', :startedAt)";

        $stmt = $database->prepare($createStatRecordSql);
        $stmt->bindValue('activityId', $activityId);
        $stmt->bindValue('userId', $userId);
        $stmt->bindValue('startedAt', $startedAt);

        $res = $stmt->execute();
        return $res;

    }

    /**
     * @param $request
     * @param $database
     * @param $durationDiff newly increase or decrease to be applied to_duration in header record (question_stat)
     * @return mixed
     */
    private function updateActivityStatisticsRecord($request, $database, $durationDiff)
    {

        if ($request instanceof Request) {
            $userId = $request->get("user_id");
            $activityId = $request->get("activity_id");
        }else{
            $userId = $request["user_id"];
            $activityId = $request["activity_id"];
        }

        $incrementSql = "update question_stat set tot_durations=tot_durations+:durationDiff where user_id = :userId and activity_id = :activityId";

        $stmt = $database->prepare($incrementSql);
        $stmt->bindValue('activityId', $activityId);
        $stmt->bindValue('userId', $userId);
        $stmt->bindValue('durationDiff', $durationDiff);

        $res = $stmt->execute();
        return $res;
    }

    /**
     *  same as updateActivityStatisticsRecord but also flag the header record as closed (end of activity for a user on specific one)
     * @param $request
     * @param $database
     * @param $durationDiff
     * @return mixed
     */
    private function stopActivityStatisticsRecord($request, $database, $durationDiff)
    {
        if ($request instanceof Request) {
            $userId = $request->get("user_id");
            $activityId = $request->get("activity_id");
            $stoppedAt = $request->get("timestamp");
        }else{
            $userId = $request["user_id"];
            $activityId = $request["activity_id"];
            $stoppedAt = $request["timestamp"];
        }

        $incrementSql = "update question_stat set tot_durations=tot_durations+:durationDiff, status='stopped', stopped_at_timestamp=:stoppedAt where user_id = :userId and activity_id = :activityId";

        $stmt = $database->prepare($incrementSql);
        $stmt->bindValue('activityId', $activityId);
        $stmt->bindValue('userId', $userId);
        $stmt->bindValue('durationDiff', $durationDiff);
        $stmt->bindValue('stoppedAt', $stoppedAt);

        $res = $stmt->execute();
        return $res;
    }


}
