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

        if ($form->isSubmitted() && $form->isValid()) {

            $new_date = $form->getData();
            $year = $new_date->getYear();
            $country = $new_date->getCountry();

            $findCountryDb = $this->getDoctrine()->getRepository(Date::class)->findByCountry($country);
            $findYearDb = $this->getDoctrine()->getRepository(Date::class)->findByYear($year);

            if (count($findCountryDb) == 0 || count($findYearDb) == 0) {

                $data= $this->fetchEnricoInformation($year, $country);

                $new_date->setYear($year);
                $new_date->setCountry($country);
                $new_date->setApi($data);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($new_date);
                $entityManager->flush();

                return $this->render('info/dates_new.html.twig', [
                    'data'           =>  $data,
                    'total_holidays' => count($data),
                    'year'           => $year,
                ]);

            } else {
                $data = $findYearDb;

//                dump($data);
//                print_r($data);

                return $this->render('info/dates_from_db.html.twig', [
                    'data'           =>  $data,
                    'total_holidays' => count($data),
                    'year'           => $year,
                ]);
            }

        }

        return $this->render("main-page/index.html.twig", [
            'form' => $form->createView(),
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
}