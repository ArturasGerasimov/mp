<?php


namespace App\Controller;

use App\Entity\Date;
use App\Form\DateType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MainController extends AbstractController
{

    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @Route("/", name="main_page")
     */
    public function main_page(Request $request): Response
    {

        $new_date= new Date();
        $form = $this->createForm(DateType::class, $new_date);
        $form->handleRequest($request);

        $supportedCountries = $this->supportedCountries();

        if ($form->isSubmitted() && $form->isValid()) {

            $new_date = $form->getData();
            $year = $new_date->getYear();
            $country = $new_date->getCountry();

            $findByCountryDb = $this->getDoctrine()->getRepository(Date::class)->findByCountry($country);
            $findByYearDb = $this->getDoctrine()->getRepository(Date::class)->findByYear($year);

            if (count($findByCountryDb) == 0 || count($findByYearDb) == 0) {

                $data= $this->fetchEnricoInformation($year, $country);
                $new_date->setYear($year);
                $new_date->setCountry($country);
                $new_date->setApi($data);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($new_date);
                $entityManager->flush();

                $currentDay = date('d');
                $currentMonth = date('m');
                $currentYear = date('Y');
                $getPublicHoliday = $this->getPublicHoliday($currentDay, $currentMonth, $currentYear, $country);
                $getWorkDay = $this->getWorkDay($currentDay, $currentMonth, $currentYear, $country);

                if ($getPublicHoliday['isPublicHoliday'] == true){
                    $chillOrNotToChill = "Today we relax, because it's holiday!";
                } elseif ($getWorkDay['isWorkDay'] == true){
                    $chillOrNotToChill = "Today your work!";
                } else {
                    $chillOrNotToChill = "Free day";
                }

                return $this->render('info/dates_new.html.twig', [
                    'data'           =>  $data,
                    'total_holidays' => count($data),
                    'year'           => $year,
                    'chill_or_not'   => $chillOrNotToChill,
                    'freeDaysAndHolidays' => $this->getFreeDaysAndHolidays($year, count($data))
                ]);
            }

            if (count($findByCountryDb) != 0 && count($findByYearDb) != 0) {

                foreach ($findByCountryDb as $gedArray){
                    $getCountry = $gedArray['d_country'];
                }

                foreach ($findByYearDb as $gedArray){
                    $getYear = $gedArray['d_year'];
                }

                $fintByYearAndCountry = $this->getDoctrine()->getRepository(Date::class)->findByTwoParameters(
                    $getCountry, $getYear);

                foreach ($fintByYearAndCountry as $newArray){
                    $totalHolidays = count($newArray['d_api']);
                }

                $currentDay = date('d');
                $currentMonth = date('m');
                $currentYear = date('Y');
                $getPublicHoliday = $this->getPublicHoliday($currentDay, $currentMonth, $currentYear, $country);
                $getWorkDay = $this->getWorkDay($currentDay, $currentMonth, $currentYear, $country);

                if ($getPublicHoliday['isPublicHoliday'] == true){
                    $chillOrNotToChill = "Today we relax, because it's holiday!";
                } elseif ($getWorkDay['isWorkDay'] == true){
                    $chillOrNotToChill = "Today your work!";
                } else {
                    $chillOrNotToChill = "Free day";
                }

                return $this->render('info/dates_from_db.html.twig', [
                    'data'                => $fintByYearAndCountry,
                    'total_holidays'      => $totalHolidays,
                    'year'                => $year,
                    'chill_or_not'        => $chillOrNotToChill,
                    'freeDaysAndHolidays' => $this->getFreeDaysAndHolidays($year, $totalHolidays)
                ]);
            }
        }

        return $this->render("main-page/index.html.twig", [
            'form'              => $form->createView(),
            'suportedCountries' => $supportedCountries
        ]);
    }

    public function fetchEnricoInformation($year, $country)
    {

        $response = $this->client->request(
            'GET',
            'https://kayaposoft.com/enrico/json/v2.0?action=getHolidaysForYear&year='.$year.'&country='.$country.'&holidayType=public_holiday'
        );

        return $response->toArray();

    }

    public function getPublicHoliday($day, $month, $year, $country)
    {

        $response = $this->client->request(
            'GET',
            'https://kayaposoft.com/enrico/json/v2.0?action=isPublicHoliday&date='.$day.'-'.$month.'-'.$year.'&country='.$country.''
        );

        return $response->toArray();

    }

    public function getWorkDay($day, $month, $year, $country)
    {

        $response = $this->client->request(
            'GET',
            'https://kayaposoft.com/enrico/json/v2.0?action=isWorkDay&date='.$day.'-'.$month.'-'.$year.'&country='.$country.''
        );

        return $response->toArray();

    }

    public function supportedCountries()
    {

        $response = $this->client->request(
            'GET',
            'https://kayaposoft.com/enrico/json/v2.0?action=getSupportedCountries'
        );

        return $response->toArray();

    }

    public function getFreeDaysAndHolidays ($year, $totalHolidays)
    {
        $monthWeekends = [];
        for ($j = 1; $j <= 12; $j++){
            $day_count = cal_days_in_month(CAL_GREGORIAN, $j, $year);
            for ($i = 1; $i <= $day_count; $i++) {
                $date = $year.'/'.$j.'/'.$i;
                $get_name = date('l', strtotime($date));
                $day_name = substr($get_name, 0, 3);
                if($day_name != 'Mon' && $day_name != 'Tue' && $day_name != 'Wed' && $day_name != 'Thu' &&
                    $day_name != 'Fri'){
                    $monthWeekends[] = $i;
                }
            }
        }

        $freeDaysAndHolidays = count($monthWeekends) + $totalHolidays;

        return $freeDaysAndHolidays;
    }


}