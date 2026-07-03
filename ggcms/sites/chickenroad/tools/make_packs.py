
# auto-generated pack maker - run build_i18n_data fr as seed
import json, importlib.util, copy
from pathlib import Path
from build_i18n_data import specs

spec = importlib.util.spec_from_file_location("bd", "build_i18n_data.py")
bd = importlib.util.module_from_spec(spec); spec.loader.exec_module(bd)

# Per-language heading + paragraph translation maps applied to fr structure
# Full packs imported from locale_packs_data.json when available
LANG_PACKS = json.loads(Path("locale_packs_data.json").read_text()) if Path("locale_packs_data.json").exists() else {}
LANG_PACKS.pop("de", None)
