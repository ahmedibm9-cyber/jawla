import base64
c = "C:/projects/jawla/research/gpc-egypt/findings/_content.b64"
d = "C:/projects/jawla/research/gpc-egypt/findings/F2.md"
imp import base64
with open(c, "r") as f:
    data = f.read()
with open(d, "w", encoding="utf-8") as f:
    f.write(base64.b64decode(data).decode("utf-8"))
print("F2.md written")
