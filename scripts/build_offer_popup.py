from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageFont


ROOT = Path(__file__).resolve().parents[1]
IMAGES = ROOT / "assets" / "images"
BACKGROUND = IMAGES / "independence-offer-popup-background-v1.png"
OUTPUT = IMAGES / "independence-offer-popup-v1.webp"
FONT_REGULAR = Path(r"C:\Windows\Fonts\arial.ttf")
FONT_BOLD = Path(r"C:\Windows\Fonts\arialbd.ttf")
FONT_SERIF = Path(r"C:\Windows\Fonts\georgiab.ttf")


def font(size: int, bold: bool = False, serif: bool = False) -> ImageFont.FreeTypeFont:
    path = FONT_SERIF if serif else (FONT_BOLD if bold else FONT_REGULAR)
    return ImageFont.truetype(str(path), size)


def trim_alpha(image: Image.Image, padding: int = 0) -> Image.Image:
    bbox = image.getchannel("A").getbbox()
    if not bbox:
        return image
    left = max(0, bbox[0] - padding)
    top = max(0, bbox[1] - padding)
    right = min(image.width, bbox[2] + padding)
    bottom = min(image.height, bbox[3] + padding)
    return image.crop((left, top, right, bottom))


def clean_logo(maximum_width: int) -> Image.Image:
    source = Image.open(IMAGES / "logo.png").convert("RGBA")
    clean = Image.new("RGBA", source.size, (0, 0, 0, 0))
    source_pixels = source.load()
    clean_pixels = clean.load()
    for y in range(source.height):
        for x in range(source.width):
            red, green, blue, alpha = source_pixels[x, y]
            lightest = min(red, green, blue)
            if lightest >= 247:
                continue
            if lightest >= 225:
                alpha = round(alpha * (247 - lightest) / 22)
            clean_pixels[x, y] = (red, green, blue, alpha)
    clean = trim_alpha(clean, 8)
    scale = maximum_width / clean.width
    return clean.resize((maximum_width, round(clean.height * scale)), Image.Resampling.LANCZOS)


def fit_height(image: Image.Image, target_height: int) -> Image.Image:
    scale = target_height / image.height
    return image.resize((round(image.width * scale), target_height), Image.Resampling.LANCZOS)


def paste_shadow(canvas: Image.Image, artwork: Image.Image, x: int, y: int) -> None:
    alpha = artwork.getchannel("A")
    shadow_alpha = alpha.filter(ImageFilter.GaussianBlur(24)).point(lambda value: round(value * 0.35))
    shadow = Image.new("RGBA", artwork.size, (10, 46, 29, 0))
    shadow.putalpha(shadow_alpha)
    canvas.alpha_composite(shadow, (x + 12, y + 24))
    canvas.alpha_composite(artwork, (x, y))


def draw_centered(draw: ImageDraw.ImageDraw, y: int, text: str, face: ImageFont.FreeTypeFont, colour: tuple[int, int, int]) -> None:
    draw.text((600, y), text, font=face, fill=colour, anchor="ma", align="center")


def build() -> None:
    canvas = Image.open(BACKGROUND).convert("RGBA").resize((1200, 1500), Image.Resampling.LANCZOS)
    draw = ImageDraw.Draw(canvas)

    # A soft central veil keeps the exact copy readable without hiding the generated artwork.
    veil = Image.new("RGBA", canvas.size, (0, 0, 0, 0))
    veil_draw = ImageDraw.Draw(veil)
    veil_draw.rounded_rectangle((118, 112, 1082, 875), radius=54, fill=(255, 253, 246, 170))
    veil = veil.filter(ImageFilter.GaussianBlur(8))
    canvas.alpha_composite(veil)

    logo = clean_logo(250)
    canvas.alpha_composite(logo, ((1200 - logo.width) // 2, 92))

    badge = (365, 194, 835, 248)
    draw.rounded_rectangle(badge, radius=27, fill=(5, 105, 57, 238))
    draw.text((600, 221), "15 AUGUST  |  INDEPENDENCE DAY", font=font(22, bold=True), fill="white", anchor="mm")

    draw_centered(draw, 285, "HAPPY", font(60, bold=True), (239, 104, 22))
    draw_centered(draw, 355, "INDEPENDENCE DAY", font(66, bold=True, serif=True), (18, 54, 116))
    draw_centered(draw, 450, "Celebrate purity. Embrace wellness.", font(27, bold=True), (6, 102, 55))

    # Modern split offer lockup.
    draw.rounded_rectangle((260, 520, 940, 703), radius=44, fill=(255, 255, 255, 225), outline=(239, 135, 50, 220), width=3)
    draw.text((385, 575), "FLAT", font=font(31, bold=True), fill=(6, 104, 55), anchor="mm")
    draw.text((555, 613), "10", font=font(126, bold=True), fill=(239, 102, 20), anchor="mm")
    draw.text((715, 584), "%", font=font(65, bold=True), fill=(18, 54, 116), anchor="mm")
    draw.text((715, 644), "OFF", font=font(50, bold=True), fill=(6, 104, 55), anchor="mm")
    draw.text((600, 742), "ON ALL PRODUCTS", font=font(27, bold=True), fill=(22, 48, 35), anchor="mm")

    code_box = (278, 784, 922, 868)
    draw.rounded_rectangle(code_box, radius=42, fill=(7, 99, 53, 245), outline=(255, 255, 255, 245), width=3)
    draw.text((405, 826), "USE CODE", font=font(22, bold=True), fill=(220, 242, 226), anchor="mm")
    draw.line((505, 804, 505, 848), fill=(255, 255, 255, 100), width=2)
    draw.text((711, 826), "FREEDOM10", font=font(37, bold=True), fill="white", anchor="mm")

    # Exact existing product cutouts keep Gawdee labels and packs authentic.
    products = [
        ("sugar-cutout-v1.png", 430, 250, 903),
        ("ghee-cutout-v1.png", 530, 458, 842),
        ("mixme-cutout-v1.png", 420, 754, 918),
    ]
    for name, height, x, y in products:
        artwork = Image.open(IMAGES / "hero-products" / name).convert("RGBA")
        artwork = fit_height(artwork, height)
        paste_shadow(canvas, artwork, x, y)

    draw.rounded_rectangle((330, 1372, 870, 1432), radius=30, fill=(255, 253, 247, 232), outline=(7, 105, 57, 180), width=2)
    draw.text((600, 1402), "PURE CHOICES FOR EVERY CELEBRATION", font=font(19, bold=True), fill=(6, 91, 49), anchor="mm")

    canvas.convert("RGB").save(OUTPUT, "WEBP", quality=94, method=6)
    print(f"Created {OUTPUT}")


if __name__ == "__main__":
    build()
