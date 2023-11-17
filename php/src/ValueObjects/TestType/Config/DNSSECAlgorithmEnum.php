<?php

namespace ResolverTest\ValueObjects\TestType\Config;

enum DNSSECAlgorithmEnum: string {
    case Alg2 = "DH|2048";
    case Alg3 = "DSA|1024";
    case Alg5 = "RSASHA1|2048";
    case Alg6 = "NSEC3DSA|1024";
    case Alg7 = "NSEC3RSASHA1|2048";
    case Alg8 = "RSASHA256|2048";
    case Alg10 = "RSASHA512|2048";
    case Alg12 = "ECCGOST";
    case Alg13 = "13";
    case Alg14 = "14";
    case Alg15 = "15";
    case Alg16 = "16";
}