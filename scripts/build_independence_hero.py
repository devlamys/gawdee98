from collections import deque
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageFont


ROOT = Path(__file__).resolve().parents[1]
IMAGES = ROOT / "assets" / "images"
CATALOG = IMAGES / "catalog"
PRODUCT_OUTPUT = IMAGES / "hero-products"
FONT_REGULAR = Path(r"C:\Windows\Fonts\arial.ttf")
FONT_BOLD = Path(r"C:\Windows\Fonts\arialbd.ttf")


def font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont:
    return ImageFont.truetype(str(FONT_BOLD if bold else FONT_REGULAR), size)


def cover(image: Image.Image, size: tuple[int, int], focus_x: float = 0.5) -> Image.Image:
    target_w, target_h = size
    scale = max(target_w / image.width, target_h / image.height)
    resized = image.resize((round(image.width * scale), round(image.height * scale)), Image.Resampling.LANCZOS)
    available_x = max(0, resized.width - target_w)
    left = round(available_x * focus_x)
    top = max(0, (resized.height - target_h) // 2)
    return resized.crop((left, top, left + target_w, top + target_h))


def fit_font(text: str, maximum_width: int, starting_size: int, minimum_size: int = 18) -> ImageFont.FreeTypeFont:
    for size in range(starting_size, minimum_size - 1, -1):
        candidate = font(size, bold=True)
        if candidate.getlength(text) <= maximum_width:
            return candidate
    return font(minimum_size, bold=True)


def trim_alpha(image: Image.Image, padding: int = 0) -> Image.Image:
    bbox = image.getchannel("A").getbbox()
    if not bbox:
        return image
    left = max(0, bbox[0] - padding)
    top = max(0, bbox[1] - padding)
    right = min(image.width, bbox[2] + padding)
    bottom = min(image.height, bbox[3] + padding)
    return image.crop((left, top, right, bottom))


def logo_layer(maximum_width: int) -> Image.Image:
    logo = Image.open(IMAGES / "logo.png").convert("RGBA")
    cleaned = Image.new("RGBA", logo.size, (0, 0, 0, 0))
    source = logo.load()
    target = cleaned.load()
    for y in range(logo.height):
        for x in range(logo.width):
            red, green, blue, alpha = source[x, y]
            lightest = min(red, green, blue)
            if lightest >= 247:
                continue
            if lightest >= 225:
                alpha = round(alpha * (247 - lightest) / 22)
            target[x, y] = (red, green, blue, alpha)
    cleaned = trim_alpha(cleaned, 8)
    scale = maximum_width / cleaned.width
    return cleaned.resize((maximum_width, round(cleaned.height * scale)), Image.Resampling.LANCZOS)


def polygon_cutout(
    source_path: Path,
    crop_box: tuple[int, int, int, int],
    polygon: list[tuple[int, int]],
    rounded_rects: list[tuple[tuple[int, int, int, int], int]] | None = None,
    feather: float = 1.4,
    clear_white_edge: bool = False,
) -> Image.Image:
    source = Image.open(source_path).convert("RGBA").crop(crop_box)
    mask = Image.new("L", source.size, 0)
    draw = ImageDraw.Draw(mask)
    draw.polygon(polygon, fill=255)
    for bounds, radius in rounded_rects or []:
        draw.rounded_rectangle(bounds, radius=radius, fill=255)
    if feather:
        mask = mask.filter(ImageFilter.GaussianBlur(feather))
    source.putalpha(mask)
    if clear_white_edge:
        pixels = source.load()
        visited: set[tuple[int, int]] = set()
        queue: deque[tuple[int, int]] = deque()

        def is_background(px: tuple[int, int, int, int]) -> bool:
            red, green, blue, alpha = px
            return alpha > 0 and min(red, green, blue) >= 230 and max(red, green, blue) - min(red, green, blue) <= 24

        for x in range(source.width):
            for y in (0, source.height - 1):
                if is_background(pixels[x, y]):
                    queue.append((x, y))
        for y in range(source.height):
            for x in (0, source.width - 1):
                if is_background(pixels[x, y]):
                    queue.append((x, y))

        while queue:
            x, y = queue.popleft()
            if (x, y) in visited or not is_background(pixels[x, y]):
                continue
            visited.add((x, y))
            red, green, blue, _ = pixels[x, y]
            pixels[x, y] = (red, green, blue, 0)
            if x > 0:
                queue.append((x - 1, y))
            if x + 1 < source.width:
                queue.append((x + 1, y))
            if y > 0:
                queue.append((x, y - 1))
            if y + 1 < source.height:
                queue.append((x, y + 1))
        for y in range(round(source.height * 0.82), source.height):
            for x in range(source.width):
                red, green, blue, alpha = pixels[x, y]
                if alpha > 0 and min(red, green, blue) >= 225 and max(red, green, blue) - min(red, green, blue) <= 30:
                    pixels[x, y] = (red, green, blue, 0)
    return trim_alpha(source, 3)


def build_cutouts() -> dict[str, Image.Image]:
    PRODUCT_OUTPUT.mkdir(parents=True, exist_ok=True)

    ghee = polygon_cutout(
        CATALOG / "gawdee-gir-cow-a2-ghee-500-ml" / "gallery-03.webp",
        (180, 500, 820, 1450),
        [
            (75, 82), (83, 150), (58, 194), (42, 235), (37, 820),
            (52, 864), (88, 878), (145, 898), (500, 898), (553, 877),
            (588, 888), (601, 820), (595, 240), (577, 194), (555, 151), (565, 82),
        ],
        [((48, 12, 588, 102), 34)],
        clear_white_edge=True,
    )

    sugar = polygon_cutout(
        CATALOG / "gawdee-bura-sugar-1-kg" / "gallery-01.webp",
        (700, 110, 1450, 1435),
        [
            (94, 38), (628, 38), (656, 68), (667, 148), (682, 1018),
            (650, 1035), (610, 1045), (160, 1080), (94, 1068), (48, 1048),
            (54, 176), (62, 94),
        ],
        [((78, 30, 649, 110), 29)],
        clear_white_edge=True,
    )

    mixme = polygon_cutout(
        CATALOG / "gawdee-mixme-choco-500-g" / "gallery-02.webp",
        (520, 200, 1460, 1390),
        [
            (184, 78), (799, 132), (858, 153), (886, 184), (912, 226),
            (797, 1018), (762, 1062), (704, 1092), (160, 1066), (96, 1048),
            (53, 1014), (39, 970), (145, 169),
        ],
        None,
    )

    cutouts = {"ghee": ghee, "sugar": sugar, "mixme": mixme}
    for name, artwork in cutouts.items():
        artwork.save(PRODUCT_OUTPUT / f"{name}-cutout-v1.png", optimize=True)
    return cutouts


def resize_height(image: Image.Image, target_height: int) -> Image.Image:
    scale = target_height / image.height
    return image.resize((round(image.width * scale), target_height), Image.Resampling.LANCZOS)


def paste_with_shadow(canvas: Image.Image, artwork: Image.Image, position: tuple[int, int], blur: int, offset: int) -> None:
    x, y = position
    alpha = artwork.getchannel("A")
    shadow_alpha = alpha.filter(ImageFilter.GaussianBlur(blur)).point(lambda value: round(value * 0.42))
    shadow = Image.new("RGBA", artwork.size, (11, 49, 29, 0))
    shadow.putalpha(shadow_alpha)
    canvas.alpha_composite(shadow, (x + round(blur * 0.35), y + offset))
    canvas.alpha_composite(artwork, (x, y))


def draw_logo_and_copy(
    canvas: Image.Image,
    mobile: bool,
    eyebrow: str,
    line_one: str,
    line_two: str,
    subtitle: str,
    button_text: str,
) -> None:
    draw = ImageDraw.Draw(canvas)
    if mobile:
        x, copy_w = 62, 565
        logo_w, logo_y = 150, 12
        eyebrow_y, line_one_y, line_two_y, subtitle_y = 87, 116, 160, 221
        line_one_size, line_two_size, subtitle_size = 37, 54, 22
        offer_box = (62, 274, 533, 320)
        button = (62, 344, 318, 412)
        offer_size, button_size = 19, 22
    else:
        x, copy_w = 278, 760
        logo_w, logo_y = 245, 35
        eyebrow_y, line_one_y, line_two_y, subtitle_y = 147, 188, 264, 370
        line_one_size, line_two_size, subtitle_size = 58, 84, 31
        offer_box = (278, 444, 902, 509)
        button = (278, 548, 642, 632)
        offer_size, button_size = 27, 28

    canvas.alpha_composite(logo_layer(logo_w), (x, logo_y))
    draw.text((x, eyebrow_y), eyebrow, font=font(18 if mobile else 24, bold=True), fill=(7, 105, 57))
    draw.text((x, line_one_y), line_one, font=fit_font(line_one, copy_w, line_one_size, 28), fill=(241, 99, 18))
    draw.text((x, line_two_y), line_two, font=fit_font(line_two, copy_w, line_two_size, 36), fill=(19, 55, 127))
    draw.text((x, subtitle_y), subtitle, font=fit_font(subtitle, copy_w, subtitle_size, 17), fill=(5, 93, 49))

    draw.rounded_rectangle(offer_box, radius=(offer_box[3] - offer_box[1]) // 2, fill=(255, 253, 247, 236), outline=(232, 122, 44, 235), width=2)
    offer = "FLAT 10% OFF   •   CODE FREEDOM10"
    draw.text(
        ((offer_box[0] + offer_box[2]) // 2, (offer_box[1] + offer_box[3]) // 2),
        offer,
        font=fit_font(offer, offer_box[2] - offer_box[0] - 30, offer_size, 15),
        fill=(8, 91, 49),
        anchor="mm",
    )

    draw.rounded_rectangle(button, radius=(button[3] - button[1]) // 2, fill=(4, 111, 54), outline=(255, 255, 255, 220), width=2)
    draw.text(
        ((button[0] + button[2]) // 2, (button[1] + button[3]) // 2),
        f"{button_text}  →",
        font=font(button_size, bold=True),
        fill="white",
        anchor="mm",
    )


def add_authentic_badge(canvas: Image.Image, mobile: bool) -> None:
    draw = ImageDraw.Draw(canvas)
    if mobile:
        bounds = (1065, 35, 1170, 140)
        size = 14
    else:
        bounds = (1850, 44, 2000, 194)
        size = 19
    draw.ellipse(bounds, fill=(255, 253, 246, 238), outline=(231, 139, 56, 220), width=2)
    cx = (bounds[0] + bounds[2]) // 2
    cy = (bounds[1] + bounds[3]) // 2
    draw.text((cx, cy - size), "100%", font=font(size + 4, bold=True), fill=(8, 91, 49), anchor="mm")
    draw.text((cx, cy + size // 2), "AUTHENTIC", font=font(size, bold=True), fill=(20, 55, 127), anchor="mm")


def compose_products(canvas: Image.Image, cutouts: dict[str, Image.Image], layout: str, mobile: bool) -> None:
    if layout == "collection":
        if mobile:
            specs = [("ghee", 315, (654, 101)), ("sugar", 300, (837, 102)), ("mixme", 292, (982, 110))]
        else:
            specs = [("ghee", 520, (1086, 137)), ("sugar", 500, (1380, 139)), ("mixme", 478, (1662, 151))]
    elif layout == "ghee":
        if mobile:
            specs = [("ghee", 346, (760, 72)), ("sugar", 250, (1000, 137))]
        else:
            specs = [("ghee", 570, (1260, 90)), ("sugar", 420, (1650, 188))]
    else:
        if mobile:
            specs = [("mixme", 345, (766, 70)), ("ghee", 260, (1012, 142))]
        else:
            specs = [("mixme", 565, (1268, 88)), ("ghee", 430, (1640, 178))]

    for name, height, position in specs:
        artwork = resize_height(cutouts[name], height)
        paste_with_shadow(canvas, artwork, position, 13 if mobile else 21, 9 if mobile else 14)


def build_slide(
    background_name: str,
    output_name: str,
    mobile: bool,
    cutouts: dict[str, Image.Image],
    layout: str,
    eyebrow: str,
    line_one: str,
    line_two: str,
    subtitle: str,
    button_text: str,
) -> None:
    size = (1200, 450) if mobile else (2060, 763)
    background = Image.open(IMAGES / background_name).convert("RGBA")
    canvas = cover(background, size, 0.5).convert("RGBA")
    draw_logo_and_copy(canvas, mobile, eyebrow, line_one, line_two, subtitle, button_text)
    compose_products(canvas, cutouts, layout, mobile)
    add_authentic_badge(canvas, mobile)
    canvas.convert("RGB").save(IMAGES / output_name, "WEBP", quality=94, method=6)


def build_contact_sheet(cutouts: dict[str, Image.Image]) -> None:
    sheet = Image.new("RGBA", (1500, 720), (26, 111, 69, 255))
    draw = ImageDraw.Draw(sheet)
    for index, (name, cutout) in enumerate(cutouts.items()):
        artwork = resize_height(cutout, 560)
        x = 90 + index * 500 + max(0, (340 - artwork.width) // 2)
        paste_with_shadow(sheet, artwork, (x, 80), 16, 10)
        draw.text((250 + index * 500, 670), name.upper(), font=font(24, bold=True), fill="white", anchor="mm")
    sheet.convert("RGB").save(PRODUCT_OUTPUT / "cutout-proof-v1.jpg", quality=92)


if __name__ == "__main__":
    cutouts = build_cutouts()
    build_contact_sheet(cutouts)

    slides = [
        (
            "hero-independence-background-v5-a.png",
            "collection",
            "15 AUGUST • INDEPENDENCE DAY SPECIAL",
            "HAPPY",
            "INDEPENDENCE DAY",
            "Celebrating purity. Embracing wellness.",
            "SHOP NOW",
            "hero-slide-independence",
        ),
        (
            "hero-independence-background-v5-b.png",
            "ghee",
            "INDEPENDENCE DAY • PURE TRADITION",
            "FREEDOM TO CHOOSE",
            "PURE GOODNESS",
            "Bilona-crafted A2 Ghee for every family table.",
            "SHOP A2 GHEE",
            "hero-slide-ghee",
        ),
        (
            "hero-independence-background-v5-c.png",
            "mixme",
            "INDEPENDENCE DAY • FAMILY WELLNESS",
            "CELEBRATE",
            "EVERYDAY WELLNESS",
            "Natural nutrition made for modern Indian families.",
            "SHOP MIXME",
            "hero-slide-mixme",
        ),
    ]

    for background, layout, eyebrow, line_one, line_two, subtitle, button, stem in slides:
        build_slide(background, f"{stem}-v5.webp", False, cutouts, layout, eyebrow, line_one, line_two, subtitle, button)
        build_slide(background, f"{stem}-mobile-v5.webp", True, cutouts, layout, eyebrow, line_one, line_two, subtitle, button)

    print("Created three exact-product Independence Day hero v5 desktop and mobile pairs.")
