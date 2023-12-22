<?php

    class HTMLVisuBewohner extends IPSModule
    {
        
        public function Create() {
            //Never delete this line!
            parent::Create();

            // Drei Eigenschaften für die dargestellten Zähler
            $this->RegisterPropertyInteger("Bewohner1", 0);
            $this->RegisterPropertyInteger('Bewohner2', 0);
            $this->RegisterPropertyInteger('Bewohner3', 0);
            $this->RegisterPropertyInteger("Bewohner1Image", 0);
            $this->RegisterPropertyInteger('Bewohner2Image', 0);
            $this->RegisterPropertyInteger('Bewohner3Image', 0);
            $this->RegisterPropertyFloat('Schriftgroesse', 1);
            // Visualisierungstyp auf 1 setzen, da wir HTML anbieten möchten
            $this->SetVisualizationType(1);
        }

        public function ApplyChanges() {
            parent::ApplyChanges();

            // Aktualisiere registrierte Nachrichten
            foreach ($this->GetMessageList() as $senderID => $messageIDs) {
                foreach($messageIDs as $messageID) {
                    $this->UnregisterMessage($senderID, $messageID);
                }
            }

            foreach(['Bewohner1', 'Bewohner2', 'Bewohner3'] as $BewohnerProperty) {
                $this->RegisterMessage($this->ReadPropertyInteger($BewohnerProperty), OM_CHANGENAME);
                $this->RegisterMessage($this->ReadPropertyInteger($BewohnerProperty), VM_UPDATE);
            }

            // Schicke eine komplette Update-Nachricht an die Darstellung, da sich ja Parameter geändert haben können
            $this->UpdateVisualizationValue($this->GetFullUpdateMessage());
        }

        public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
            foreach(['Bewohner1', 'Bewohner2', 'Bewohner3'] as $index => $BewohnerProperty) {
                if ($SenderID === $this->ReadPropertyInteger($BewohnerProperty)) {
                    switch ($Message) {
                        case OM_CHANGENAME:
                            // Teile der HTML-Darstellung den neuen Namen mit
                            $this->UpdateVisualizationValue(json_encode([
                                'name' . ($index + 1) => $Data[0]
                            ]));
                            break;

                        case VM_UPDATE:
                            // Teile der HTML-Darstellung den neuen Wert mit. Damit dieser korrekt formatiert ist, holen wir uns den von der Variablen via GetValueFormatted
                            $this->UpdateVisualizationValue(json_encode(['value' . ($index + 1) => GetValue($this->ReadPropertyInteger($BewohnerProperty))]));
                            break;
                    }
                }
            }
        }

        public function RequestAction($Ident, $value) {
            // Nachrichten von der HTML-Darstellung schicken immer den Ident passend zur Eigenschaft und im Wert die Differenz, welche auf die Variable gerechnet werden soll
            $variableID = $this->ReadPropertyInteger($Ident);
            if (!IPS_VariableExists($variableID)) {
                $this->SendDebug('Error in RequestAction', 'Variable to be updated does not exist', 0);
                return;
            }
                // Umschalten des Werts der Variable
            $currentValue = GetValue($variableID);
            SetValue($variableID, !$currentValue);
        }
        
        public function GetVisualizationTile() {
            // Füge ein Skript hinzu, um beim laden, analog zu Änderungen bei Laufzeit, die Werte zu setzen
            // Obwohl die Rückgabe von GetFullUpdateMessage ja schon JSON-codiert ist wird dennoch ein weiteres mal json_encode ausgeführt
            // Damit wird dem String Anführungszeichen hinzugefügt und eventuelle Anführungszeichen innerhalb werden korrekt escaped
            $initialHandling = '<script>handleMessage(' . json_encode($this->GetFullUpdateMessage()) . ');</script>';

            // Füge statisches HTML aus Datei hinzu
            $module = file_get_contents(__DIR__ . '/module.html');

            // Gebe alles zurück. 
            // Wichtig: $initialHandling nach hinten, da die Funktion handleMessage ja erst im HTML definiert wird
            return $module . $initialHandling;
        }

            // Generiere eine Nachricht, die alle Elemente in der HTML-Darstellung aktualisiert
            private function GetFullUpdateMessage() {
                $Bewohner1ID = $this->ReadPropertyInteger('Bewohner1');
                $Bewohner2ID = $this->ReadPropertyInteger('Bewohner2');
                $Bewohner3ID = $this->ReadPropertyInteger('Bewohner3');
                $Bewohner1Exists = IPS_VariableExists($Bewohner1ID);
                $Bewohner2Exists = IPS_VariableExists($Bewohner2ID);
                $Bewohner3Exists = IPS_VariableExists($Bewohner3ID);
                $result = [
                    'Bewohner1' => $Bewohner1Exists,
                    'Bewohner2' => $Bewohner2Exists,
                    'Bewohner3' => $Bewohner3Exists,
                ];
                $result['fontsize'] = $this->ReadPropertyFloat('Schriftgroesse');

                if ($Bewohner1Exists) {
                    $result['name1'] = IPS_GetName($Bewohner1ID);
                    $result['value1'] = GetValueBoolean($Bewohner1ID);

                    // Prüfe vorweg, ob ein Bild ausgewählt wurde
                    $imageID = $this->ReadPropertyInteger('Bewohner1Image');
                    if (IPS_MediaExists($imageID)) {
                        $image = IPS_GetMedia($imageID);
                        if ($image['MediaType'] === MEDIATYPE_IMAGE) {
                            $imageFile = explode('.', $image['MediaFile']);
                            $imageContent = '';
                            // Falls ja, ermittle den Anfang der src basierend auf dem Dateitypen
                            switch (end($imageFile)) {
                                case 'bmp':
                                    $imageContent = 'data:image/bmp;base64,';
                                    break;
            
                                case 'jpg':
                                case 'jpeg':
                                    $imageContent = 'data:image/jpeg;base64,';
                                    break;
            
                                case 'gif':
                                    $imageContent = 'data:image/gif;base64,';
                                    break;
            
                                case 'png':
                                    $imageContent = 'data:image/png;base64,';
                                    break;
            
                                case 'ico':
                                    $imageContent = 'data:image/x-icon;base64,';
                                    break;
                            }

                            // Nur fortfahren, falls Inhalt gesetzt wurde. Ansonsten ist das Bild kein unterstützter Dateityp
                            if ($imageContent) {
                                // Hänge base64-codierten Inhalt des Bildes an
                                $imageContent .= IPS_GetMediaContent($imageID);
                                $result['image1'] = $imageContent;
                            }

                        }
                    }
                    else{
                        $imageContent = 'data:image/png;base64,';
                        $imageContent .= base64_encode(file_get_contents('./imgs/placeholder.png'));
                    }                    
                    

                }
                
                
                if ($Bewohner2Exists) {
                    $result['name2'] = IPS_GetName($Bewohner2ID);
                    $result['value2'] = GetValueBoolean($Bewohner2ID);

                    // Prüfe vorweg, ob ein Bild ausgewählt wurde
                    $imageID = $this->ReadPropertyInteger('Bewohner2Image');
                    if (IPS_MediaExists($imageID)) {
                        $image = IPS_GetMedia($imageID);
                        if ($image['MediaType'] === MEDIATYPE_IMAGE) {
                            $imageFile = explode('.', $image['MediaFile']);
                            $imageContent = '';
                            // Falls ja, ermittle den Anfang der src basierend auf dem Dateitypen
                            switch (end($imageFile)) {
                                case 'bmp':
                                    $imageContent = 'data:image/bmp;base64,';
                                    break;
            
                                case 'jpg':
                                case 'jpeg':
                                    $imageContent = 'data:image/jpeg;base64,';
                                    break;
            
                                case 'gif':
                                    $imageContent = 'data:image/gif;base64,';
                                    break;
            
                                case 'png':
                                    $imageContent = 'data:image/png;base64,';
                                    break;
            
                                case 'ico':
                                    $imageContent = 'data:image/x-icon;base64,';
                                    break;
                            }

                            // Nur fortfahren, falls Inhalt gesetzt wurde. Ansonsten ist das Bild kein unterstützter Dateityp
                            if ($imageContent) {
                                // Hänge base64-codierten Inhalt des Bildes an
                                $imageContent .= IPS_GetMediaContent($imageID);
                                $result['image2'] = $imageContent;
                            }
                            else{
                                $imageContent = 'data:image/png;base64,';
                                $imageContent .= base64_encode(file_get_contents(__DIR__ . '/imgs/placeholder.png'));
                            }
                        }
                    }
                                        
                    

                }


                if ($Bewohner3Exists) {
                    $result['name3'] = IPS_GetName($Bewohner3ID);
                    $result['value3'] = GetValueBoolean($Bewohner3ID);

                    // Prüfe vorweg, ob ein Bild ausgewählt wurde
                    $imageID = $this->ReadPropertyInteger('Bewohner3Image');
                    if (IPS_MediaExists($imageID)) {
                        $image = IPS_GetMedia($imageID);
                        if ($image['MediaType'] === MEDIATYPE_IMAGE) {
                            $imageFile = explode('.', $image['MediaFile']);
                            $imageContent = '';
                            // Falls ja, ermittle den Anfang der src basierend auf dem Dateitypen
                            switch (end($imageFile)) {
                                case 'bmp':
                                    $imageContent = 'data:image/bmp;base64,';
                                    break;
            
                                case 'jpg':
                                case 'jpeg':
                                    $imageContent = 'data:image/jpeg;base64,';
                                    break;
            
                                case 'gif':
                                    $imageContent = 'data:image/gif;base64,';
                                    break;
            
                                case 'png':
                                    $imageContent = 'data:image/png;base64,';
                                    break;
            
                                case 'ico':
                                    $imageContent = 'data:image/x-icon;base64,';
                                    break;
                            }

                            // Nur fortfahren, falls Inhalt gesetzt wurde. Ansonsten ist das Bild kein unterstützter Dateityp
                            if ($imageContent) {
                                // Hänge base64-codierten Inhalt des Bildes an
                                $imageContent .= IPS_GetMediaContent($imageID);
                                $result['image3'] = $imageContent;
                            }
                            else{
                                $imageContent = 'data:image/png;base64,';
                                $imageContent .= base64_encode(file_get_contents(__DIR__ . '/imgs/placeholder.png'));
                            }
                        }
                    }
                                        
                    

                }
            
                return json_encode($result);
                
            }

    
    }
 
?>
