<?php

namespace app\Models\Enums;

enum EncryptionMode : string
{
    /**
     * Encryption is explicitly disabled for this dedicated server.
     * This is used for direct connections and BeatTogether servers >= v1.31.
     */
    case None = "none";
    /**
     * Encryption is negotiated via handshake with the master server, which will transfer state to the dedicated server.
     * For game clients < 1.29.
     */
    case MasterHandshake = "master_handshake";
    /**
     * Encryption is negotiated via direct handshake with the dedicated server.
     * For game clients >= v1.29.
     * Fully deprecated as of v1.31.
     */
    case DirectHandshake = "direct_handshake";
    /**
     * The dedicated server uses Enet with UseSsl (DTLS).
     * For game clients >= v1.31 (official & specific third party servers that implement it).
     */
    case EnetDtls = "enet_dtls";

    public function describe(): string
    {
        return match ($this) {
            self::None => "Disabled",
            self::MasterHandshake => "Legacy TLS (Master)",
            self::DirectHandshake => "Legacy TLS (Direct)",
            self::EnetDtls => "DTLS Enabled"
        };
    }
}
