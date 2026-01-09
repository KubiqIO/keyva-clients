class KeyvaClient:
    def __init__(self, api_key: str, base_url: str = "https://api.keyva.io"):
        self.api_key = api_key
        self.base_url = base_url

    def ping(self) -> str:
        return "pong"
