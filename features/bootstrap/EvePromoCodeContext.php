<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\TestCase;

/**
 * Defines application features from the specific context.
 */
class EvePromoCodeContext extends TestCase implements Context
{
  const CAMPAIGN_SERVICE = 'campaign_service';
  
  private $campaigns = [];
  private $codes = [];
  private $error = '';

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct()
  {
    require_once(dirname(__FILE__).'/../../../../config/ProjectConfiguration.class.php');

    $configuration = ProjectConfiguration::getApplicationConfiguration('default', 'dev', true);
    sfContext::createInstance($configuration);
  }

  private function getContext()
  {
    return sfContext::getInstance();
  }
  
  private function getCampaignService()
  {
    return $this->getContext()->getContainer()->get(EvePromoCodeContext::CAMPAIGN_SERVICE);
  }

  /**
   * @Given aucune OP n'existe
   */
  public function aucuneOpNexiste()
  {
    $this->getCampaignService()->removeCampaign('PROMOCODES-TEST');
    $this->campaigns = [];
  }

  /**
   * @When je demande la liste des OP
   */
  public function jeDemandeLaListeDesOp()
  {
    $this->campaigns = $this->getCampaignService()->listCampaigns();
  }

  /**
   * @Then la liste contient :count OP
   */
  public function laListeContientOp($count)
  {
      $this->assertCount(intval($count), $this->campaigns);
  }

  /**
   * @When j'ajoute une OP nommée :name
   */
  public function jAjouteUneOpNommee($campaign_name)
  {
    $this->campaigns[] = $this->getCampaignService()->addCampaign($campaign_name);
  }

  /**
   * @Then le nom de l'OP est :count
   */
  public function leNomDeLopEst($campaign_name)
  {
      $this->assertEquals($campaign_name, $this->campaigns[0]->name);
  }

  /**
   * @Given l'op suivante:
   */
  public function opSuivante(TableNode $table)
  {
    foreach ($table as $row) {
      $campaign = new PromoCampaign();
      $campaign->name = $row['name'];
      $campaign->expiration = $row['expiration'];
      $campaign->card_type_id = $row['card_type_id'];
      $campaign->save();
      
      $this->campaigns[] = $campaign;
    }
    
    $this->codes = [];
  }

  /**
   * @When un code est généré avec :length caractères
   */
  public function unCodeEstGenere($length)
  {
    $this->codes[] = $this->getCampaignService()->generateCode($this->campaigns[0], $length);
    $this->campaigns[0]->PromoCodes->add($this->codes[0]);
    $this->campaigns[0]->save();
  }

  /**
   * @Then le code doit respecter le format :format
   */
  public function leCodeDoitRespecterLeFormat($format)
  {
    $this->assertRegExp($format, $this->codes[0]->code);
  }

  /**
   * @When je demande de générer :count codes de longueur :length
   */
  public function jeDemandeDeGenererCodes($count, $length)
  {
    try {
      $this->codes = $this->getCampaignService()->generateCodes($this->campaigns[0], $length, $count);
      $this->campaigns[0]->PromoCodes = $this->codes;
      $this->campaigns[0]->save();
    } catch(Exception $e) {
      $this->error = $e->getMessage();
    }
  }

  /**
   * @Then la liste contient :count codes
   * @Then la liste contient :count code
   */
  public function laListeContientCodes($count)
  {
    $this->assertCount(intval($count), $this->codes);
  }

  /**
   * @Then le message d'erreur est :message
   */
  public function leMessageDerreurEst($message)
  {
    $this->assertEquals($message, $this->error);
  }





}
