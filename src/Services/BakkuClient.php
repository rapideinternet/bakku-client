<?php declare(strict_types=1);

namespace RapideSoftware\BakkuClient\Services;

class BakkuClient extends BakkuClientService
{
    // This class exists to resolve potential BindingResolutionException in consuming applications
    // where 'BakkuClient' might be incorrectly sought as a concrete class in the Services namespace.
    // It simply extends BakkuClientService to provide a valid target for such lookups.
}
