<?php
namespace backend\helpers;

use backend\models\Fields;
use SimpleXMLElement;

// Радиус земли
define('EARTH_RADIUS', 6372795);

/**
 * Парсер kml файла полей
 */
class KmlParser
{
    protected $fields = [];

    /**
     * Парсер kml файла полей
     *
     * @param $filePath - путь к файлу с расширением kml
     *
     * @return Fields[]|null - массив объектов класса Fields или null в случае ошибки обработки
     */
    public function parser($filePath)
    {
        if (file_exists($filePath)) {
            $xml = simplexml_load_file($filePath);
            $isError = false;
            foreach ($xml->Document->Placemark as $placemark) {
                $isError = $this->handlePlacemark($placemark);
            }

            if ($isError) {
                return null;
            }
        } else {
            return null;
        }

        return $this->fields;
    }

    /**
     * @param $polygon
     *
     * @return mixed
     */
    protected function getPolygonCoordinates($polygon)
    {
        $coordinates = (string) $polygon->outerBoundaryIs->LinearRing->coordinates;

        return $this->handleCoordinates($coordinates);
    }

    protected function getCircleCoordinates($point)
    {
        $coordinates = (string) $point->coordinates;

        return $this->handleCoordinates($coordinates);
    }

    protected function getLineCoordinates($lineString)
    {
        $coordinates = (string) $lineString->coordinates;

        return $this->handleCoordinates($coordinates);
    }
    
    
    // определение длины отрезка
  	protected function getLengthLine($x1, $y1, $x2, $y2)	{
	
		$lat1  = $x1 * M_PI / 180;
		$lat2  = $x2 * M_PI / 180;
		$long1 = $y1 * M_PI / 180;
		$long2 = $y2 * M_PI / 180;

		// косинусы и синусы широт и разницы долгот

	    $cl1 = cos($lat1);
		$cl2 = cos($lat2);
		$sl1 = sin($lat1);
		$sl2 = sin($lat2);
		$delta = $long2 - $long1;
		$cdelta = cos($delta);
		$sdelta = sin($delta);
        
		// вычисления длины большого круга

		$y = sqrt(pow($cl2 * $sdelta, 2) + pow($cl1 * $sl2 - $sl1 * $cl2 * $cdelta, 2));
		$x = $sl1 * $sl2 + $cl1 * $cl2 * $cdelta;
		$ad = atan2($y, $x);
		$dist = $ad * EARTH_RADIUS;    	
		
		return $dist;
	}
	
	
     protected function getPolygonPerimeter($coordinates = 0) {  

        $perimeter = 0.00;	     

		$coordinatesMas = explode(',0,' , $coordinates);
	     	      
		for($i = 0; $i < count($coordinatesMas); $i++) {			
			$coordinatesMas[$i] = str_replace(',0', '', $coordinatesMas[$i]);
			$coordinatesMas[$i] = explode(',' , $coordinatesMas[$i]);			
		}
	     
	     for($i = 0; $i < count($coordinatesMas) - 1; $i++) {	     		
	    
		    $dist = $this->getLengthLine($coordinatesMas[$i][0], $coordinatesMas[$i][1], $coordinatesMas[$i+1][0], $coordinatesMas[$i+1][1]);	
		    $perimeter +=  $dist;
	     }	     	     		     
	     	       	     	     
	     $perimeter = number_format(($perimeter/1000), 3);
         if (empty($perimeter)) $perimeter =0;
	     
	     return $perimeter;
		  
	}

    protected function handleCoordinates($coordinates)
    {
        $coordinatesArray = explode(' ', $coordinates);

        $newCoordinates = [];
        foreach ($coordinatesArray as $coordinate) {
            $data = explode(',', trim($coordinate));

            $newCoordinates[] = $data[1];
            $newCoordinates[] = $data[0];
            $newCoordinates[] = $data[2];
        }

        $newCoordinates = join(',', $newCoordinates);

        return $this->prepareValue($newCoordinates);
    }

    /**
     * @param SimpleXMLElement $description
     *
     * @return array
     */
    protected function handleAttributes($description)
    {
        $attributes = $description->attributes();

        return [
            $this->prepareAttributeValue($attributes->color),
            $this->prepareAttributeValue($attributes->addr),
            $this->prepareAttributeValue($attributes->ride_begin),
            $this->prepareAttributeValue($attributes->ride_end),
            $this->prepareAttributeValue($attributes->width),
        ];
    }

    private function handlePlacemark($placemark)
    {
        $field              = new Fields();
        $field->name        = $this->prepareValue($placemark->name);
        $field->description = $this->prepareValue($placemark->description);
        list($field->color, $field->addr, $field->ride_begin, $field->ride_end, $field->width) =
            $this->handleAttributes($placemark->description);

        if (isset($placemark->Polygon)) {
            $field->type        = Fields::TYPE_POLYGON;
            $field->coordinates = $this->getPolygonCoordinates($placemark->Polygon);
            $field->perimeter = $this->getPolygonPerimeter($field->coordinates);
        } elseif (isset($placemark->Point)) {
            $field->type        = Fields::TYPE_CIRCLE;
            $field->coordinates = $this->getCircleCoordinates($placemark->Point);
        } elseif (isset($placemark->LineString)) {
            $field->type        = Fields::TYPE_LINE;
            $field->coordinates = $this->getLineCoordinates($placemark->LineString);
        } else {
            return true;
        }

        $this->fields[] = $field;

        return false;
    }

    private function prepareValue($value)
    {
        return trim(preg_replace('/\t+/', '', $value));
    }

    private function prepareAttributeValue($value)
    {
        $result = (string) $value;

        return is_null($result) || $result == '' ? null : $result;
    }
}
