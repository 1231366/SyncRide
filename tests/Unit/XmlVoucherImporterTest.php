<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\XmlVoucherImporter;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Regression for the additive fields (distributor_code, reference_no, resort,
 * leg_code) the weekly Excel export needs — captured straight from the XML
 * feed without touching the importer's existing dedup/matching behaviour.
 */
final class XmlVoucherImporterTest extends TestCase
{
    private const XML = <<<'XML'
        <transferGroupings DateTime="2026-02-03+01:00">
           <Header><companyCode>DTS</companyCode></Header>
           <Groupings>
              <Grouping>
                 <serviceId>25053974</serviceId>
                 <serviceDate>2026-03-01</serviceDate>
                 <serviceType>TR</serviceType>
                 <serviceLegCode>IN</serviceLegCode>
                 <serviceUnitVehicleName>Shared</serviceUnitVehicleName>
                 <serviceStartTime>20:35</serviceStartTime>
                 <serviceStartPoint>Airport OPO</serviceStartPoint>
                 <serviceTargetPoint>Hotel da Bolsa</serviceTargetPoint>
                 <bookings>
                    <bookingItem>
                       <distributorCode>SUNTR</distributorCode>
                       <bookingReference>SUNTR_UN3756</bookingReference>
                       <bookingItemVoucher>SUNTR_UN3756</bookingItemVoucher>
                       <paxLeadName>Bavage, Emily</paxLeadName>
                       <paxADT>1</paxADT>
                       <paxCHD>0</paxCHD>
                       <paxINF>0</paxINF>
                       <remarks>Mobile: +44 7807240343</remarks>
                       <pickup>
                          <pickupPointType>Flight</pickupPointType>
                          <pickupPoint>
                             <flightCompanyCode>U2</flightCompanyCode>
                             <flightNumber>2875</flightNumber>
                          </pickupPoint>
                       </pickup>
                       <dropoff>
                          <dropoffPointType>Accommodation</dropoffPointType>
                          <accommodationResortName>Porto</accommodationResortName>
                          <accommodationtName>Hotel da Bolsa</accommodationtName>
                       </dropoff>
                    </bookingItem>
                 </bookings>
              </Grouping>
              <Grouping>
                 <serviceId>25053975</serviceId>
                 <serviceDate>2026-03-05</serviceDate>
                 <serviceType>TR</serviceType>
                 <serviceLegCode>OT</serviceLegCode>
                 <serviceUnitVehicleName>Private</serviceUnitVehicleName>
                 <serviceStartTime>05:00</serviceStartTime>
                 <serviceStartPoint>Hotel Cristal Porto</serviceStartPoint>
                 <serviceTargetPoint>Airport OPO</serviceTargetPoint>
                 <bookings>
                    <bookingItem>
                       <distributorCode>ITAK</distributorCode>
                       <bookingReference>13670154</bookingReference>
                       <bookingItemVoucher>13670154</bookingItemVoucher>
                       <paxLeadName>Zabrzanski, Krzysztof</paxLeadName>
                       <paxADT>2</paxADT>
                       <paxCHD>0</paxCHD>
                       <paxINF>0</paxINF>
                       <remarks/>
                       <pickup>
                          <pickupPointType>Accommodation</pickupPointType>
                          <accommodationResortName>Porto</accommodationResortName>
                          <accommodationtName>Hotel Cristal Porto</accommodationtName>
                       </pickup>
                       <dropoff>
                          <dropoffPointType>Flight</dropoffPointType>
                          <pickupPoint>
                             <flightCompanyCode>W6</flightCompanyCode>
                             <flightNumber>1086</flightNumber>
                          </pickupPoint>
                       </dropoff>
                    </bookingItem>
                 </bookings>
              </Grouping>
           </Groupings>
        </transferGroupings>
        XML;

    private function seededDb(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE Services (
            ID INTEGER PRIMARY KEY, company_id INTEGER,
            serviceDate TEXT, serviceStartTime TEXT, paxADT INT, paxCHD INT, paxBBY INT,
            serviceStartPoint TEXT, serviceTargetPoint TEXT, FlightNumber TEXT,
            NomeCliente TEXT, ClientNumber TEXT, serviceType INT,
            distributor_code TEXT, reference_no TEXT, resort TEXT, leg_code TEXT
        )');
        return $pdo;
    }

    public function testCapturesDistributorReferenceResortAndLegCode(): void
    {
        $pdo = $this->seededDb();
        $inserted = (new XmlVoucherImporter($pdo, 7))->importFromString(self::XML);
        $this->assertSame(2, $inserted);

        $rows = $pdo->query('SELECT * FROM Services ORDER BY serviceDate')->fetchAll(PDO::FETCH_ASSOC);

        $this->assertSame('SUNTR', $rows[0]['distributor_code']);
        $this->assertSame('SUNTR_UN3756', $rows[0]['reference_no']);
        $this->assertSame('Porto', $rows[0]['resort']);
        $this->assertSame('IN', $rows[0]['leg_code']);
        $this->assertSame('U22875', $rows[0]['FlightNumber']);

        $this->assertSame('ITAK', $rows[1]['distributor_code']);
        $this->assertSame('13670154', $rows[1]['reference_no']);
        $this->assertSame('Porto', $rows[1]['resort']);
        $this->assertSame('OT', $rows[1]['leg_code']);
    }

    public function testExistingFieldsUnaffected(): void
    {
        $pdo = $this->seededDb();
        (new XmlVoucherImporter($pdo, 7))->importFromString(self::XML);

        $row = $pdo->query("SELECT * FROM Services WHERE serviceDate = '2026-03-01'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Airport OPO', $row['serviceStartPoint']);
        $this->assertSame('Hotel da Bolsa', $row['serviceTargetPoint']);
        $this->assertSame('Bavage, Emily', $row['NomeCliente']);
        $this->assertSame('+44 7807240343', $row['ClientNumber']);
        $this->assertSame(7, (int) $row['company_id']);
    }
}
