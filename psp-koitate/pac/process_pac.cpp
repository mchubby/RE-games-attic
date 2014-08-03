// cl -W3 -D_WIN32_WINNT=0x500 -EHsc process_pac.cpp -Zi -MDd
// cl -W3 -D_WIN32_WINNT=0x500 -EHsc process_pac.cpp -MT
// g++ -Wall -o process_pac.exe process_pac.cpp

// Contains portions written Feb. 2009 by Erik Möller,
// with slight modifications by Werner Lemberg.


#include <fstream>
#include <iostream>
#include <string>

#ifdef _WIN32_WINNT
#include <BaseTsd.h>
typedef UINT_PTR uintptr_t;
#else
#include <stdint.h>
#endif

// Define some fixed size types.

typedef unsigned char uint8;
typedef unsigned short uint16;
typedef unsigned int uint32;

// Try to figure out what endian this machine is using. Note that the test
// below might fail for cross compilation; additionally, multi-byte
// characters are implementation-defined in C preprocessors.

#if (('1234' >> 24) == '1')
#elif (('4321' >> 24) == '1')
  #define BIG_ENDIAN
#else
  #error "Couldn't determine the endianness!"
#endif

// A simple 32-bit pixel.

union Pixel32
{
  Pixel32()
  : integer(0) { }
  Pixel32(uint8 bi, uint8 gi, uint8 ri, uint8 ai = 255)
  {
    b = bi;
    g = gi;
    r = ri;
    a = ai;
  }

  uint32 integer;

  struct
  {
#ifdef BIG_ENDIAN
    uint8 a, r, g, b;
#else // BIG_ENDIAN
    uint8 b, g, r, a;
#endif // BIG_ENDIAN
  };
};



// TGA Header struct to make it simple to dump a TGA to disc.

#if defined(_MSC_VER) || defined(__GNUC__)
#pragma pack(push, 1)
#pragma pack(1)               // Dont pad the following struct.
#endif

struct TGAHeader
{
  uint8   idLength,           // Length of optional identification sequence.
          paletteType,        // Is a palette present? (1=yes)
          imageType;          // Image data type (0=none, 1=indexed, 2=rgb,
                              // 3=grey, +8=rle packed).
  uint16  firstPaletteEntry,  // First palette index, if present.
          numPaletteEntries;  // Number of palette entries, if present.
  uint8   paletteBits;        // Number of bits per palette entry.
  uint16  x,                  // Horiz. pixel coord. of lower left of image.
          y,                  // Vert. pixel coord. of lower left of image.
          width,              // Image width in pixels.
          height;             // Image height in pixels.
  uint8   depth,              // Image color depth (bits per pixel).
          descriptor;         // Image attribute flags.
};

#if defined(_MSC_VER) || defined(__GNUC__)
#pragma pack(pop)
#endif


bool
WriteTGA(const std::string &filename,
         const Pixel32 *pxl,
         uint16 width,
         uint16 height)
{
  std::ofstream file(filename.c_str(), std::ios::binary);
  if (file)
  {
    TGAHeader header;
    memset(&header, 0, sizeof(TGAHeader));
    header.imageType  = 2;
    header.width = width;
    header.height = height;
    header.depth = 32;
    header.descriptor = 0x20;

    file.write((const char *)&header, sizeof(TGAHeader));
    file.write((const char *)pxl, sizeof(Pixel32) * width * height);

    return true;
  }
  return false;
}

// Render the specified character as a colored glyph with a colored outline
// and dump it to a TGA.

void
WritePacToTga(unsigned char const *pacBuffer,
              int pacFileSize,
              const std::string &pacFileName)
{
    std::string tgaFileName(pacFileName + ".tga");

    if (*reinterpret_cast<const uint32*>(pacBuffer)!=1 || *reinterpret_cast<const uint32*>(pacBuffer+0x4)!=1)
    {
        std::cerr << "Cannot Get Metrics: " << pacFileName << std::endl;
//        return;
    }
    int imgWidth = *reinterpret_cast<const uint16*>(pacBuffer+0xC),
        imgHeight = *reinterpret_cast<const uint16*>(pacBuffer+0xE),
        imgSize = imgWidth * imgHeight;

    int rowPadding = *reinterpret_cast<const uint16*>(pacBuffer+0x24);


    // Detect palette header
    int upperBound = 0x100;
    if (upperBound > pacFileSize)
    {
        upperBound = pacFileSize;
    }
    int paletteHeaderOffset = 0x10;
    while (*reinterpret_cast<const uint32*>(pacBuffer+paletteHeaderOffset)!=0x03030001 && paletteHeaderOffset < upperBound)
    {
        paletteHeaderOffset += 0x04;
    }
    if (paletteHeaderOffset >= upperBound)
    {
        std::cerr << "Palette Not Found: " << pacFileName << std::endl;
        return;
    }
    paletteHeaderOffset += 0x10;

    // Allocate data for our image and clear it out to transparent.
    Pixel32 *pxl = new Pixel32[imgSize];
    memset(pxl, 0, sizeof(Pixel32) * imgSize);

    // For coverage tests
    bool *crib = new bool[imgSize];
    memset(crib, 0, sizeof(bool) * imgSize);

    // Allocate palette.
    Pixel32 *pal = new Pixel32[0x100];
    memset(pal, 0, sizeof(Pixel32) * 0x100);
    for (int i = 0; i < 0x100; ++i )
    {
        *(pal+i) = *(Pixel32*)(pacBuffer+paletteHeaderOffset+i*4);
        uint8 r = (pal+i)->b;
        (pal+i)->b = (pal+i)->r;
        (pal+i)->r = r;
//    if(i){std::cerr << "Read 1 pal @ R:" << std::hex << (int)((pal+i)->r) << " G:" << (int)((pal+i)->g) << " B:" << (int)((pal+i)->b) << " A:" << (int)((pal+i)->a) << "\n";return;}
    }

    const int GRIDX=16;
    const int GRIDY=8;
    const int PICFRAGSIZE = 1920 * GRIDX * GRIDY;

    int bitmapOffset = paletteHeaderOffset + 0x400 + (rowPadding >= 0x100? rowPadding / 4 : 0);
    int remainingPixels = PICFRAGSIZE;

    uintptr_t base = reinterpret_cast<uintptr_t>(pacBuffer+bitmapOffset);


    for (int ybase = 0; ybase < imgHeight; ybase += GRIDY )
    {
std::cerr << "remainingPixels=" << std::hex << remainingPixels << std::endl;
std::cerr << "base=" << std::hex << (base-reinterpret_cast<uintptr_t>(pacBuffer)) << std::endl;
        for (int xbase = 0; xbase < imgWidth; xbase += GRIDX )
        {
            for (int yoff = 0; yoff < GRIDY; ++yoff )
            {
                for (int xoff = 0; xoff < GRIDX; ++xoff )
                {
                    pxl[(ybase+yoff)*imgWidth + xbase + xoff] = pal[*(reinterpret_cast<const unsigned char *>(base++))];
                    crib[(ybase+yoff)*imgWidth + xbase + xoff] = true;
                    --remainingPixels;
                }
            }
        }
        if ( remainingPixels )
        {
//            base += 0x100;
            base += (rowPadding >= 0x100? rowPadding / 2 : 0);
        }
        else
        {
            base += 0x80;
std::cerr << "value=" << std::hex << (*(reinterpret_cast<const unsigned int *>(base))) << std::endl;
std::cerr << "remainingPixels=0; base=" << std::hex << (base-reinterpret_cast<uintptr_t>(pacBuffer)) << std::endl;
goto end;
        }
    }

    for (int y = 0; y < imgHeight; ++y )
    {
        for (int x = 0; x < imgWidth; ++x )
        {
            if(!crib[y*imgWidth+x])
std::cerr << "Notput " << x << "," << y << std::endl;
        }
    }
end:
    // Dump the image to disk.
    WriteTGA(tgaFileName, pxl, imgWidth, imgHeight);

    delete [] pal;
    delete [] pxl;

}



int
main(int argc,
     char **argv)
{
  if (argc != 2)
  {
    std::cerr << "Converts a .pac to a .tga\n";
    std::cerr << "usage: process_pac <input_pac>\n";
    return 1;
  }

  // Open up source file.
  std::ifstream pacFile(argv[1], std::ios::binary);
  if (pacFile)
  {
    // Read the entire file to a memory buffer.
    pacFile.seekg(0, std::ios::end);
    std::fstream::pos_type pacFileSize = pacFile.tellg();
    pacFile.seekg(0);
    unsigned char *pacBuffer = new unsigned char[pacFileSize];
    pacFile.read((char *)pacBuffer, pacFileSize);


    // Dump to a tga.
    WritePacToTga(pacBuffer,
                  pacFileSize,
                  argv[1]
    );

    // Now that we are done it is safe to delete the memory.
    delete [] pacBuffer;
  }


  return 1;
}

