<?php

use CSLFW\Includes\CargoAPI\Cargo;
use CSLFW\Includes\CargoAPI\CargoAPIV2;
use setasign\Fpdi\Fpdi;
if ( ! defined( 'ABSPATH' ) ) exit;
class CSLFW_Cargo_Process_Shipment_Label extends CSLFW_Cargo_Job
{
    private $cargo;
    public $action_name;
    public $last_order_id;
    public $id;
    public $file_name;

    public function __construct($id = null, $action_name = '', $last_order_id = null, $file_name = '')
    {
        if (!empty($id)) $this->id = $id;
        if (!empty($args)) $this->args = $args;
        $this->action_name = $action_name;
        $this->last_order_id = $last_order_id;
        $this->file_name = $file_name;

        $api_key = get_option('cslfw_cargo_api_key');

        if ($api_key) {
            $this->cargo = new CargoAPIV2();
        } else {
            $this->cargo = new Cargo();
        }
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function handle()
    {
        $logs = new CSLFW_Logs();
        $logs->add_debug_message("CARGO QUEUE:: started job handle", ['order_id' => $this->id, 'action_name' => $this->action_name]);
        $progress = get_transient( 'cslfw_bulk_shipment_print_process');

        // Loop through the array of objects
        if ($progress) {
            $progress[] = $this->id;
        } else {
            $progress = [$this->id];
        }
        set_transient( 'cslfw_bulk_shipment_print_process', $progress, 300);

        $cargoShipping = new CSLFW_Cargo_Shipping($this->id);

        $file = "assets/labels/{$this->file_name}.pdf";
        $pathToFile = CSLFW_PATH . $file;

        $logs->add_debug_message("CARGO QUEUE:: finished job handle", ['file_name' => $this->file_name,'file' => $pathToFile]);

        if ($cargoShipping->get_shipment_data()) {
            $cargoShipping = new CSLFW_Cargo_Shipping();
            $shipmentIds   = $cargoShipping->order_ids_to_shipment_ids([$this->id]);
            $pdfLabel      = $cargoShipping->getShipmentLabel( implode( ',', $shipmentIds ), [$this->id]);

            if (!$pdfLabel->errors) {
                if (file_exists($pathToFile)) {
                    $this->add_external_content_to_pdf($pathToFile, $pdfLabel->data);
                } else {
                    $this->create_pdf_from_url($pdfLabel->data, $pathToFile);
                }
            } else {
                $logs->add_debug_message("CARGO QUEUE:: Failed to create label", ['order_id' => $this->id, 'action_name' => $this->action_name, 'shipment' => $pdfLabel]);
            }

            $newProgressStatus = 'printed label';
        } else {
            $newProgressStatus = "Order skipped because it doesn't have shipments created'";
            $logs->add_debug_message("CARGO QUEUE:: $newProgressStatus", ['order_id' => $this->id, 'action_name' => $this->action_name]);
        }

        set_transient( 'cslfw_bulk_shipment_print_process', $progress, 300);

        if (!is_null($this->last_order_id) && $this->id === $this->last_order_id) {
            $file_url = CSLFW_URL . $file;

            set_transient("cslfw_print_label", $file_url, 60);

            delete_transient('cslfw_bulk_shipment_print');

            sleep(5);
            delete_transient( 'cslfw_bulk_shipment_print_process');
        }
    }


    public function toArgsArray()
    {
        return [
            'obj_id' => $this->id,
            'action_name' => $this->action_name,
            'last_order_id' => $this->last_order_id,
            'file_name' => $this->file_name
        ];
    }


    function add_external_content_to_pdf($existingPdfPath, $externalUrl) {
        $logs = new CSLFW_Logs();

        try {
            $tempPdf1 = tempnam(sys_get_temp_dir(), 'pdf1');
            $tempPdf2 = tempnam(sys_get_temp_dir(), 'pdf2');

            file_put_contents($tempPdf1, file_get_contents($existingPdfPath));
            file_put_contents($tempPdf2, file_get_contents($externalUrl));

            $pdf = new Fpdi();

            $pageCount = $pdf->setSourceFile($tempPdf1);
            for ($i = 1; $i <= $pageCount; $i++) {
                $size = $pdf->getTemplateSize($pdf->importPage($i));
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $templateId = $pdf->importPage($i);
                $pdf->useTemplate($templateId);
            }

            $pageCount = $pdf->setSourceFile($tempPdf2);
            for ($i = 1; $i <= $pageCount; $i++) {
                $size = $pdf->getTemplateSize($pdf->importPage($i));
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $templateId = $pdf->importPage($i);
                $pdf->useTemplate($templateId);
            }

            $pdf->Output($existingPdfPath, 'F');

            wp_delete_file($tempPdf1);
            wp_delete_file($tempPdf2);

            $logs->add_debug_message('PDF Created', ['d' => $existingPdfPath]);
        } catch (Exception $e) {
            delete_transient('bulk_shipment_print');
            delete_transient( 'cslfw_bulk_shipment_print_process');

            $logs->add_debug_message("Error creating PDF: " . $e->getMessage());

            die("Error creating PDF: " . esc_html($e->getMessage()));
        }
        $logs->add_debug_message('LABELS APPENDING', ['d' => $externalUrl]);

        return $existingPdfPath;
    }

    function create_pdf_from_url($url, $outputPdfPath) {
        $logs = new CSLFW_Logs();

        $folder = CSLFW_PATH . "assets/labels";
        $logs->add_debug_message('LABELS FOLDER', ['d' => $folder]);

        // Initialize WP_Filesystem
        global $wp_filesystem;
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }

        if ( ! $wp_filesystem->is_dir( $folder ) ) {
            $logs->add_debug_message('FOLDER NOT FOUND, CREATING FOLDER', ['d' => $folder]);
            $wp_filesystem->mkdir( $folder, 0700 );
        }

        // Step 1: Retrieve content from the provided URL
        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) {
            die( 'Error: Could not fetch content from the URL' );
        }

        $content = wp_remote_retrieve_body( $response );

        if ( empty( $content ) ) {
            die( 'Error: Could not fetch content from the URL' );
        }

        $wp_filesystem->put_contents( $outputPdfPath, $content, FS_CHMOD_FILE );

        $logs->add_debug_message('PDF Created', ['d' => $outputPdfPath]);
    }
}
