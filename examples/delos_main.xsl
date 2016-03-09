<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">
<xsl:output method="html" version="4.0" encoding="UTF-8"/>

<!--  Basic rule: copy everything not specified and process the childs -->
<xsl:template match="@*|node()">
	<xsl:copy><xsl:apply-templates select="@*|node()" /></xsl:copy>
</xsl:template>

<!--
   Main transformations
-->

<!-- Rewriting of links -->
<xsl:template match="a" >

    <xsl:copy>
        <xsl:copy-of select="@*" />

        <xsl:choose>
             <!--  Prevent switching to safari in webapp mode -->
            <xsl:when test="@href and not(@onclick)">
                <xsl:attribute name="onclick">window.location=this.getAttribute("href");return false;</xsl:attribute>
                <xsl:copy-of select="node()" />
            </xsl:when>

            <!-- links without href are just anchors -->
            <xsl:otherwise>
                <xsl:copy-of select="node()" />
            </xsl:otherwise>
        </xsl:choose>

    </xsl:copy>
</xsl:template>

</xsl:stylesheet>