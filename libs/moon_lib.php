<?
class MoonPhase {
	private $day;
	private $month;
	private $year;
	
	private $age; 
	private $phase;
	private $DI;
	private $LA;
	private $LO;
	
	public function __construct($day="",$month="",$year="") {
		if ($day=="") 
			$day=date("d");
		if ($month=="") 
			$month=date("m");
		if ($year=="") 
			$year=date("Y");
		
		$this->day=$day;
		$this->month=$month;
		$this->year=$year;
		
		
		$this->calculate();
	}
	
	function showPicture() {
		$age=$this->age;
		return("<img src='images/moon/moon$age.gif' title='$age' alt='$age' />");
	}

	public function showAge() {
		//Moon's age
		return $this->age;
	}
	
	public function showPhase() {
		//Moon's phase
		return $this->phase;
	}
	
	public function showDistance() {
		//Distance (Earth radii)
		return $this->DI;
	}
	
	public function showLatitude() {
		//Ecliptic latitude (degrees)
		return $this->LA;
	}
	
	public function showLongitude() {
		//Ecliptic longitude (degrees)
		return $this->LO;
	}
	
	private function calculate($state='2') {

		$Y = $this->year;
		$M = $this->month;
		$D = $this->day;
		$P2=2*3.14159;

		$YY=$Y-intval((12-$M)/10);
		$MM=$M+9;
		if ($MM>=12) {
			$MM=$MM-12;
		}
		$K1=intval(365.25*($YY+4712));
		$K2=intval(30.6*$MM+.5);
		$K3=intval(intval(($YY/100)+49)*.75)-38;

		// JD for dates in Julian calendar
		$J=$K1+$K2+$D+59;
		if ($J>2299160) {
		// For Gregorian calendar
		 $J-=$K3;
		}

		// J is the Julian date at 12h UT on day in question

		// Calculate illumination (synodic) phase
		$V=($J-2451550.1)/29.530588853;
		$V=$V-intval($V);
		if ($V<0) {
			$V=$V+1;
		}
		$IP=$V;

		// Moon's age in days
		$AG=$IP*29.53;

		
		/*
		
		// Convert phase to radians
		$IP=$IP*$P2;

		// Calculate distance from anomalistic phase
		$V=($J-2451562.2)/27.55454988;
		$V=$V-intval($V);
		if ($V<0) {
			$V=$V+1;
		}
		$DP=$V;
		$DP=$DP*$P2; // Convert to radians
		$DI=60.4-3.3*cos($DP)-.6*cos(2*$IP-$DP)-.5*cos(2*$IP);

		// Calculate latitude from nodal (draconic) phase
		$V=($J-2451565.2)/27.212220817;
		$V=$V-intval($V);
		if ($V<0) {
			$V=$V+1;
		}
		$NP=$V;

		// Convert to radians
		$NP=$NP*$P2;
		$LA=5.1*sin($NP);

		// Calculate longitude from sidereal motion
		$V=($J-2451555.8)/27.321582241;
		// Normalize values to range 0 to 1
		$V=$V-intval($V);
		if ($V<0) {
			$V=$V+1;
		}

		$RP=$V;
		$LO=360*$RP+6.3*sin($DP)+1.3*sin(2*$IP-$DP)+.7*sin(2*$IP);

		// phases from http://home.hiwaay.net/~krcool/Astro/moon/moonphase/
		$Phase = Array( "new", "waxing crescent", "in its first quarter", "waxing gibbous", "full", "waning gibbous", "in its last quarter", "waning crescent" );
		switch (intval($AG)) {
			case 0:
			case 29:
				$ThisPhase = 0;
				break;
			case 1:
			case 2:
			case 3:
			case 4:
			case 5:
			case 6:
				$ThisPhase = 1;
				break;
			case 7:
				$ThisPhase = 2;
				break;
			case 8:
			case 9:
			case 10:
			case 11:
			case 12:
			case 13:
				$ThisPhase = 3;
				break;
			case 14:
				$ThisPhase = 4;
				break;
			case 15:
			case 16:
			case 17:
			case 18:
			case 19:
			case 20:
			case 21:
				$ThisPhase = 5;
				break;
			case 22:
				$ThisPhase = 6;
				break;
			case 23:
			case 24:
			case 25:
			case 26:
			case 27:
			case 28:
				$ThisPhase = 7;
				break;
		}
		
		$this->phase=$Phase[$ThisPhase];
		$this->DI=$DI;;
		$this->LA=$LA;
		$this->LO=$LO;
		
		 
		*/

		$age=intval($AG);
		$this->age=$age;
	}

}
?>